<template>
	<div component="NewslettersForm" class="pb-6">
		<v-row>
			<v-col class="d-flex">
				<v-btn class="mr-2" color="primary" :disabled="!isUpdatable" @click="save(true)"
					>Uložiť a zavrieť<v-icon class="ml-2">mdi-content-save</v-icon></v-btn
				>

				<v-btn class="mr-2" color="primary" v-if="itemExists" :disabled="!isUpdatable" @click="save()"
					>Uložiť <v-icon class="ml-2">mdi-content-save</v-icon></v-btn
				>
				<!-- <v-btn
					class="mr-2"
					color="primary"
					v-if="newsletter"
					:disabled="!newsletter.id"
					@click="$emit('duplicate', newsletter)"
					>Duplikovať <v-icon class="ml-2">mdi-content-copy</v-icon></v-btn
				> -->
				<v-btn class="mr-2" color="primary" v-if="itemExists" :disabled="!isUpdatable" @click="$emit('remove', newsletter)"
					>Zmazať <v-icon class="ml-2">mdi-delete</v-icon></v-btn
				>

				<v-btn class="mr-2" color="secondary" v-if="itemExists" :disabled="!isUpdatable" @click="$emit('send', newsletter)"
					>Odoslať <v-icon class="ml-2">mdi-email-fast</v-icon></v-btn
				>

				<v-btn class="mr-2" @click="$emit('close')">Zavrieť <v-icon class="ml-2">mdi-close</v-icon></v-btn>

				<v-spacer></v-spacer>

				<v-btn class="mr-2" @click="$emit('preview')" v-if="itemExists"
					>Ukážka <v-icon class="ml-2">mdi-file-find</v-icon></v-btn
				>

				<NewslettersTestSender :newsletter="data" v-if="itemExists" />

				<NewslettersContentHistory
					:newsletter="data"
					:disabled="!isUpdatableAndExists"
					:restorable="isContentDirty"
					@select="onHistoryItemSelected"
					@restore="onContentRestored"
				/>
			</v-col>
		</v-row>
		<v-form ref="form" v-model="form.valid" lazy-validation>
			<v-row>
				<v-col sm="12" md="12" lg="3">
					<!-- Title -->
					<v-text-field
						v-model="data.title"
						label="Názov"
						:counter="100"
						:rules="validationRules.title"
						:disabled="!isUpdatable"
						filled
					/>
					<!-- Subject -->
					<v-text-field
						v-model="data.subject"
						label="Predmet"
						:counter="100"
						:rules="validationRules.subject"
						:disabled="!isUpdatable"
						filled
					/>
					<!-- Topics -->
					<v-select
						v-model="data.topic_id"
						:items="topicsItems"
						:rules="validationRules.topic_id"
						:disabled="!isUpdatable"
						label="Kanál"
						item-value="id"
						item-text="title"
						filled
					></v-select>
					<!-- Send At -->
					<div class="d-flex">
						<v-menu
							ref="sendAtDateMenu"
							v-model="form.visible.sendAtDateMenu"
							:close-on-content-click="false"
							:return-value.sync="form.send_at_date"
							transition="scale-transition"
							offset-y
							min-width="290px"
						>
							<template v-slot:activator="{ on, attrs }">
								<v-text-field
									id="send_at_date"
									class="mr-2"
									:value="viewDate(form.send_at_date)"
									:disabled="!isUpdatable"
									:rules="validationRules.send_at_date"
									label="Dátum odoslania"
									v-bind="attrs"
									v-on="on"
									@click:clear="onSendAtDateClear"
									clearable
									readonly
									filled
								></v-text-field>
							</template>
							<v-date-picker
								v-model="form.send_at_date"
								first-day-of-week="1"
								@change="onSendAtDateChanged"
								no-title
								scrollable
							>
								<v-spacer></v-spacer>
								<v-btn text color="primary" @click="form.visible.sendAtDateMenu = false"> Zrušiť </v-btn>
								<v-btn text color="primary" @click="$refs.sendAtDateMenu.save(form.send_at_date)"> Potvrdiť </v-btn>
							</v-date-picker>
						</v-menu>
						<v-menu
							ref="sendAtTimeMenu"
							v-model="form.visible.sendAtTimeSelector"
							:close-on-content-click="false"
							:nudge-right="40"
							:return-value.sync="form.send_at_time"
							transition="scale-transition"
							offset-y
							max-width="290px"
							min-width="290px"
						>
							<template v-slot:activator="{ on, attrs }">
								<v-text-field
									id="send_at_time"
									v-model="form.send_at_time"
									:disabled="!isUpdatable"
									prepend-inner-icon="mdi-timer"
									:rules="validationRules.send_at_time"
									label="Čas"
									v-bind="attrs"
									v-on="on"
									readonly
									filled
								></v-text-field>
							</template>
							<v-time-picker
								v-if="form.visible.sendAtTimeSelector"
								v-model="form.send_at_time"
								label="Čas"
								format="24hr"
								@click:minute="$refs.sendAtTimeMenu.save(form.send_at_time)"
								scrollable
							></v-time-picker>
						</v-menu>
					</div>
					<!-- Attachments -->
					<NewslettersAttachments
						class="mb-6"
						:newsletter="data"
						:max-count="5"
						:disabled="!isUpdatableAndExists"
						@change="onAttachmentsChange"
					/>

					<!-- Segments -->
					<Segmentation
						class="mb-6"
						:aggregation.sync="data.segments_aggregate"
						:customers.sync="getSetCustomers"
						:segments.sync="data.segments"
						:pricelists.sync="data.pricelists"
						:use-customers-unregistered="false"
						:disabled="!isUpdatable"
					/>

					<!-- Stats -->
					<NewsletterStats
						:newsletter="newsletter"
						:exporting="[states.exportingRecipients].includes(state)"
						@export="$emit('exportRecipients')"
						@build="$emit('build')"
					/>
				</v-col>
				<v-col sm="12" md="12" lg="9" class="d-flex flex-column align-stretch">
					<!-- Content -->
					<div v-if="form.content.html" style="max-width: 1070px">
						<NewslettersContent v-model="form.content.html" :disabled="!isUpdatable">
							<template v-slot:editor-toolbar-after>
								<div class="d-flex" style="position: absolute; right: 7px; top: 6px">
									<NewslettersContentSource
										v-model="form.content.html"
										:disabled="!isUpdatable"
										@save="onContentSourceUpdate"
										button-small
									/>
									<v-btn color="primary" :disabled="!isUpdatableAndExists || !isContentDirty" @click="saveContent" small>
										Uložiť<v-icon class="ml-2">mdi-content-save</v-icon>
									</v-btn>
								</div>
							</template>
						</NewslettersContent>
					</div>

					<!-- Templates -->
					<NewsletterTemplates v-else @select="onTemplateSelected" />
				</v-col>
			</v-row>
		</v-form>
	</div>
</template>

<script>
import { mapMutations, mapGetters } from 'vuex';
import { formatDateTime, arrayRemap } from '@/helpers';
import { clone } from '@/helpers';
import { omit } from 'lodash';

import { defaultNewsletter, defaultNewsletterContent } from '@/store/newsletters';
import newsletters from '@/mixins/newsletters';

import Segmentation from '@/components/.global/Segmentation.vue';
import NewslettersAttachments from './NewslettersForm/NewslettersAttachments.vue';
import NewslettersContent from './NewslettersForm/NewslettersContent.vue';
import NewslettersContentSource from './NewslettersForm/NewslettersContentSource.vue';
import NewslettersContentHistory from './NewslettersForm/NewslettersContentHistory.vue';
import NewslettersTestSender from './NewslettersForm/NewslettersTestSender.vue';
import NewsletterStats from './NewslettersForm/NewsletterStats.vue';
import NewsletterTemplates from './NewslettersForm/NewsletterTemplates.vue';
import moment from 'moment';

export const states = {
	idle: 'idle',
	exportingRecipients: 'exportingRecipients',
};

const topicsTitleMap = {
	1: 'Oznamy - zákazníci',
	2: 'Marketing - dodacie adresy, používatelia',
};

export default {
	name: 'NewslettersForm',

	mixins: [newsletters],

	components: {
		Segmentation,
		NewslettersAttachments,
		NewslettersContent,
		NewslettersContentSource,
		NewslettersContentHistory,
		NewslettersTestSender,
		NewsletterStats,
		NewsletterTemplates,
	},

	props: {
		state: {
			type: String,
			default: () => states.idle,
		},
		newsletter: {
			type: Object,
		},
	},

	data: () => ({
		states,

		form: {
			valid: false,

			visible: {
				sendAtDateMenu: false,
				sendAtTimeSelector: false,
			},

			send_at_date: '',
			send_at_time: '',
			content: { ...defaultNewsletterContent },
		},

		data: clone(defaultNewsletter),
	}),

	created() {
		this.prepareData();
	},

	methods: {
		...mapMutations(['openSnackbar']),

		prepareData() {
			if (!this.newsletter) {
				return;
			}
			this.data = {
				...this.data,
				...clone(this.newsletter),
			};
			this.form.send_at_date = this.data.send_at ? formatDateTime(this.data.send_at, null, 'YYYY-MM-DD') : '';
			this.form.send_at_time = this.data.send_at ? this.viewTime(this.data.send_at) : '';
			this.prepareContent();
		},

		prepareContent() {
			if (this.newsletter?.content) {
				this.data.content = { ...this.newsletter.content };
			}
			if (!this.data.content) {
				this.data.content = { ...defaultNewsletterContent };
			}
			if (this.data.content.id) {
				this.form.content = { ...this.data.content };
			}
		},

		prepareAttachments() {
			this.data.attachments = [...this.newsletter.attachments];
		},

		viewDate(date) {
			return formatDateTime(date, '', 'DD.MM.YYYY');
		},

		viewTime(date) {
			return formatDateTime(date, '', 'HH:mm');
		},

		onSendAtDateChanged() {
			if (!this.form.send_at_time) {
				this.form.send_at_time = '00:00';
			}
		},

		onSendAtDateClear() {
			this.form.send_at_date = '';
			this.form.send_at_time = '';
		},

		onSendAtDateOrTimeChanged() {
			if (!this.form.send_at_date) {
				this.form.send_at_time = '';
				this.data.send_at = clone(defaultNewsletter.send_at);
				return;
			}
			// Run validation on inputs
			this.formRef.inputs.filter(i => ['send_at_date', 'send_at_time'].includes(i.id)).map(i => i.validate());
			this.data.send_at = `${this.form.send_at_date}T${this.form.send_at_time || '00:00'}+02:00`;
		},

		onTemplateSelected(template) {
			this.form.content.html = template.html;
		},

		onContentSourceUpdate(html) {
			this.form.content.html = html;
		},

		onContentRestored() {
			this.form.content = { ...this.data.content };
		},

		onHistoryItemSelected(historyItem) {
			this.form.content = { ...historyItem };
		},

		onAttachmentsChange(attachments) {
			this.data.attachments = attachments;
		},

		save(close = false) {
			if (!this.formRef.validate()) {
				this.openSnackbar({ message: 'Vo formulári sú chyby', color: 'error' });
				return;
			}

			if (!this.itemExists || this.isContentDirty) {
				this.data.content = { ...this.form.content, ...omit(defaultNewsletterContent, ['html']) };
			}

			this.$emit('save', { newsletter: this.data, close });
		},

		saveContent() {
			this.data.content = { ...this.form.content, ...omit(defaultNewsletterContent, ['html']) };
			this.$emit('saveContent', this.form.content);
		},
	},

	computed: {
		...mapGetters(['getNewslettersTopics', 'getSegmentsList']),

		topicsItems() {
			return this.getNewslettersTopics.map(item => ({
				...item,
				title: topicsTitleMap[item.id],
			}));
		},

		formRef() {
			return this.$refs.form;
		},

		itemExists() {
			return !!this.data.id;
		},

		isUpdatable() {
			return this.$_isUpdatable(this.data);
		},

		isUpdatableAndExists() {
			return this.isUpdatable && this.itemExists;
		},

		isContentDirty() {
			return this.form.content.html != this.data.content.html;
		},

		// Re-map back and forth
		getSetCustomers: {
			get() {
				return arrayRemap(this.data.customers, { company_name: 'name' });
			},
			set(value) {
				this.data.customers = arrayRemap(value, { name: 'company_name' });
			},
		},

		validationRules() {
			// prettier-ignore
			return {
				title: [
					v => !!v || 'Názov je povinný',
					v => v.length < 100 || 'Maximálna dĺžka 100 znakov',
				],
				subject: [
					v => !!v || 'Predmet je povinný',
					v => v.length >= 3 || 'Minimálna dĺžka 3 znaky',
					v => v.length < 100 || 'Maximálna dĺžka 100 znakov',
				],
				topic_id: [
					v => !!v || 'Kanál je povinný',
				],
				send_at_date: [
					v => !v || moment() <= moment(`${this.form.send_at_date}T23:59:59`) || 'Dátum odoslania je nesprávny'
				],
				send_at_time: [
					v => !v || moment() <= moment(this.data.send_at) || 'Čas odoslania je nesprávny'
				]
			}
		},
	},

	watch: {
		'form.send_at_date': 'onSendAtDateOrTimeChanged',
		'form.send_at_time': 'onSendAtDateOrTimeChanged',
		'newsletter.content.id': 'prepareContent',
		'newsletter.attachments': 'prepareAttachments',
	},
};
</script>

<style lang="less">
[component='NewslettersForm'] {
}
</style>
