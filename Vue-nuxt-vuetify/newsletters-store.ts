import { GetterTree, ActionTree, MutationTree } from 'vuex';
import { pick } from 'lodash';
import FileDownload from 'js-file-download';

import { RootState } from '@/store';

import { Newsletter } from '@/types';

import { clone, toKebabCase } from '@/helpers';
import { SegmentationAggregation } from './segments';

export enum NewsletterStates {
	Draft = 'draft',
	Ready = 'ready_to_send',
	Sending = 'sending',
	SendingStopped = 'sending_stopped',
	Sent = 'sent',
}

export const newsletterStates = {
	[NewsletterStates.Draft]: { title: 'Rozpracovaný', color: 'red' },
	[NewsletterStates.Ready]: { title: 'Pripravený na odoslanie', color: 'orange' },
	[NewsletterStates.Sending]: { title: 'Odosiela sa', color: 'orange' },
	[NewsletterStates.Sent]: { title: 'Odoslaný', color: 'green' },
};

export enum NewsletterBuildStates {
	NotBuilt = 'not_built',
	Building = 'building',
	Built = 'built',
}

export const defaultFilters: Newsletter.Filter = {
	state: null,
	sentAtFrom: null,
	sentAtTo: null,
	topic: [],
	segment: [],
	sort: ['created_at:desc'],
};

export const defaultNewsletterContent: Newsletter.Content = {
	id: null,
	html: '',
	created_at: '',
};

export const defaultNewsletter: Newsletter = {
	id: null,
	uuid: '',
	topic_id: null,
	state: NewsletterStates.Draft,
	build_state: NewsletterBuildStates.NotBuilt,
	title: '',
	subject: '',
	segments_aggregate: SegmentationAggregation.Union,
	recipient_count: null,
	impressions_count: 0,
	clicks_count: 0,
	send_at: '',
	send_start_at: '',
	sent_at: '',
	created_at: '',
	updated_at: '',
	deleted_at: '',
	// Includes
	topic: null,
	customers: [],
	segments: [],
	pricelists: [],
	content: { ...defaultNewsletterContent },
	content_history: [],
	attachments: [],
};

const state = () => ({
	list: <Newsletter[]>[],
	newsletter: <Newsletter>null,
	filter: <Newsletter.Filter>clone(defaultFilters),
	pagination: {
		page: 1,
		limit: 10,
	},
	meta: {
		pageCount: 0,
		totalCount: 0,
	},
	topics: <Newsletter.Topic[]>[],
	templates: <Newsletter.Template[]>[],
});

export type NewslettersModule = ReturnType<typeof state>;

const getters: GetterTree<NewslettersModule, RootState> = {
	getNewslettersList: state => state.list,
	getSelectedNewsletter: state => state.newsletter,
	getNewslettersFilter: state => state.filter,
	getNewslettersPagination: state => state.pagination,
	getNewslettersMeta: state => state.meta,
	getNewslettersTopics: state => state.topics,
	getNewslettersTemplates: state => state.templates,
};

const mutations: MutationTree<NewslettersModule> = {
	setNewslettersList: (state, list) => (state.list = list),
	setSelectedNewsletter: (state, newsletter) => (state.newsletter = newsletter),
	setNewslettersFilter: (state, filter) => (state.filter = filter),
	setNewslettersPagination: (state, pagination) => (state.pagination = pagination),
	setNewslettersMeta: (state, meta) => (state.meta = meta),
	setNewslettersTopics: (state, topics) => (state.topics = topics),
	setNewslettersTemplates: (state, templates) => (state.templates = templates),

	// TODO: refactor
	updateNewsletterInStore: (state, data: Newsletter) => {
		if (state.newsletter?.id == data.id) {
			state.newsletter = {
				...state.newsletter,
				...data,
			};
		}

		if (state.list.some(i => i.id == data.id)) {
			state.list = state.list.map(i => {
				return {
					...i,
					...(i.id == data.id ? pick(data, Object.keys(i)) : {}),
				};
			});
		}
	},

	removeNewsletterInStore: (state, data: Newsletter) => {
		if (state.newsletter?.id == data.id) {
			state.newsletter = {
				...state.newsletter,
				...data,
			};
		}
		state.list = state.list.filter(i => i.id != data.id);
	},
};

const listInclude = ['topic', 'segments'];
const detailInclude = ['customers', 'segments', 'pricelists', 'content', 'content_history', 'attachments'];

const actions: ActionTree<NewslettersModule, RootState> = {
	async loadNewslettersTopics({ commit }) {
		const { data } = await this.$axios.$get('newsletters-topics');
		commit('setNewslettersTopics', data);
		return data;
	},

	async loadNewslettersTemplates({ commit }) {
		const { data } = await this.$axios.$get('newsletters-templates');
		commit('setNewslettersTemplates', data);
		return data;
	},

	async loadNewsletters({ state, commit }) {
		const { data, meta } = await this.$axios.$get('newsletters', {
			params: sanitizeBeforeLoad({ ...state.filter, ...state.pagination, include: listInclude }),
		});
		commit('setNewslettersList', data);
		commit('setNewslettersMeta', {
			...state.meta,
			pageCount: meta.pagination.total_pages,
			totalCount: meta.pagination.total,
		});
		return { data, meta };
	},

	async loadNewsletter({ commit }, id: number) {
		const response = await this.$axios.$get(`newsletters/${id}`, {
			params: { include: detailInclude },
		});
		commit('setSelectedNewsletter', response);
		return response;
	},

	async saveNewsletter({ commit }, newsletter: Newsletter) {
		const url = 'newsletters' + (newsletter.id ? `/${newsletter.id}` : '');
		const method = newsletter.id ? '$patch' : '$post';
		const response = await this.$axios[method](url, sanitizeBeforeSave(newsletter), {
			params: { include: detailInclude },
		});
		commit('setSelectedNewsletter', response);
		return response;
	},

	async saveNewsletterContent({ commit }, { newsletter, content: { html } }) {
		const response = await this.$axios.$patch(
			`newsletters/${newsletter.id}`,
			{ content: html },
			{ params: { include: detailInclude } }
		);
		commit('setSelectedNewsletter', response);
		return response;
	},

	async removeNewsletter({}, newsletter: Newsletter) {
		return await this.$axios.$delete(`newsletters/${newsletter.id}`);
	},

	async buildNewsletter({}, { newsletter, fresh, sync }) {
		return await this.$axios.$post(`newsletters/${newsletter.id}/build`, { fresh, sync });
	},

	async sendNewsletterTest({}, { newsletter, recipients }) {
		return await this.$axios.$post(`newsletters/${newsletter.id}/send-test`, { recipients });
	},

	async sendNewsletter({}, newsletter: Newsletter) {
		return await this.$axios.$post(`newsletters/${newsletter.id}/send`);
	},

	async sendStopNewsletter({}, newsletter: Newsletter) {
		return await this.$axios.$post(`newsletters/${newsletter.id}/send-stop`);
	},

	async sendResumeNewsletter({}, newsletter: Newsletter) {
		return await this.$axios.$post(`newsletters/${newsletter.id}/send-resume`);
	},

	async storeNewsletterAttachment({ state }, { newsletter, formData }) {
		const response = await this.$axios.$post(`newsletters-attachments/${newsletter.id}`, formData, {
			headers: {
				'Content-Type': 'multipart/form-data',
			},
		});

		if (state.newsletter) {
			state.newsletter.attachments = response.data;
		}

		return response;
	},

	async removeNewsletterAttachment({ state }, attachment) {
		const response = await this.$axios.$delete(`newsletters-attachments/${attachment.id}`);

		if (state.newsletter) {
			state.newsletter.attachments = response.data;
		}

		return response;
	},
};

const sanitizeBeforeLoad = (filters: Newsletter.Filter) => {
	const data = clone(filters);

	// let prop: string;
	// let value: any;
	// for ([prop, value] of Object.entries(filters)) {
	// 	switch (prop) {
	// 		default:
	// 			data[prop] = value;
	// 	}
	// }
	return data;
};

const sanitizeBeforeSave = (newsletter: Newsletter) => {
	return {
		...pick(newsletter, [
			'title',
			'subject',
			'segments_aggregate',
			'topic_id',
			'send_at',
			'customers',
			'segments',
			'pricelists',
		]),
		content: !newsletter.content?.id ? newsletter.content.html : '',
	};
};

export default {
	namespaced: false,
	state,
	getters,
	mutations,
	actions,
};
