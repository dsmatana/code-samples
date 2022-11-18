<template>
	<div component="BannersList">
		<v-btn color="primary" @click="$emit('create')">Vytvoriť<v-icon class="ml-2">mdi-plus</v-icon></v-btn>
		<v-pagination v-model="getSetPage" class="mb-4" :length="pageCount" :total-visible="7" circle></v-pagination>
		<v-data-table
			class="list"
			:options="options"
			:headers="headers"
			:items="mappedList"
			:server-items-length="totalCount"
			v-on="passEventsToParent(['update:options', 'update:sort-by', 'update:sort-desc'])"
			@click:row="openDetail"
			hide-default-footer
			disable-pagination
			disable-filtering
			must-sort
		>
			<template v-slot:[`item.recipient_count`]="{ item }">
				<v-progress-circular
					v-if="$_isBuilding(item) || $_isSending(item) || $_isSendingStopped(item)"
					:value="item.recipient_count"
					color="primary"
					:size="35"
					indeterminate
					><span class="progress-text" :class="{ dark: $vuetify.theme.dark }">{{
						item.recipient_count
					}}</span></v-progress-circular
				>
				<span v-else>{{ item.recipient_count }}</span>
			</template>

			<template v-slot:[`item.actions`]="{ item }">
				<v-tooltip top>
					<template v-slot:activator="{ on }">
						<div v-on="on">
							<v-btn
								v-if="!$_isInSendingProcess(item)"
								@click.stop="$emit('build', item)"
								:disabled="$_isBuilding(item) || $_isSent(item)"
								icon
							>
								<v-icon>mdi-refresh</v-icon>
							</v-btn>
							<v-btn v-if="$_isSending(item)" @click.stop="$emit('send-stop', item)" :disabled="$_isBuilding(item)" icon>
								<v-icon>mdi-pause</v-icon>
							</v-btn>
							<v-btn v-if="$_isSendingStopped(item)" @click.stop="$emit('send-resume', item)" icon>
								<v-icon>mdi-play</v-icon>
							</v-btn>
						</div>
					</template>
					{{ getRecipientsActionTooltip(item) }}
				</v-tooltip>
			</template>

		</v-data-table>
		<v-pagination v-model="getSetPage" class="my-4" :length="pageCount" :total-visible="7" circle></v-pagination>
	</div>
</template>

<script>
import { formatDateTime } from '@/helpers';
import newsletters from '@/mixins/newsletters';

// TODO: needs refactoring

export default {
	name: 'NewslettersList',

	mixins: [newsletters],

	props: {
		list: {
			type: Array,
			default: [],
			required: true,
		},
		page: {
			type: Number,
			default: 1,
			required: true,
		},
		pageCount: {
			type: Number,
			default: 1,
			required: true,
		},
		totalCount: {
			type: Number,
			required: true,
		},
		options: {
			type: Object,
		},
	},

	data: () => ({
		headers: [
			{ text: 'Názov', value: 'title' },
			{ text: 'Kanál', value: 'topic.title', sortable: false, width: 100 },
			// { text: 'Segmenty', value: 'segments', sortable: false, width: 220 },
			{ text: '# adresátov', value: 'recipient_count', align: 'center', width: 120 },
			{ text: '', value: 'actions', sortable: false, width: 40, align: 'center' }, // Disabled
			{ text: '# impresií', value: 'impressions_count', align: 'center', width: 120 },
			{ text: '# kliknutí', value: 'clicks_count', align: 'center', width: 120 },
			{ text: 'Plánovné odoslanie', value: 'send_at', sortable: true, width: 160 },
			{ text: 'Odoslaný', value: 'sent_at', sortable: true, width: 160 },
			{ text: 'Vytvorený', value: 'created_at', sortable: true, width: 160 },
			// { text: '', value: 'action', sortable: false, width: 40, align: 'center' }, // Disabled
		],
		visibleItemMenu: false,
	}),

	methods: {
		openDetail(item) {
			this.$router.push({ name: 'newsletters-id-edit', params: { id: item.id } });
		},

		passEventsToParent(events) {
			return events.reduce((result, event) => {
				result[event] = e => this.$emit(event, e);
				return result;
			}, {});
		},
	},

	computed: {
		mappedList() {
			return this.list.map(item => ({
				...item,
				// segments: item.segments.map(i => i.title),
				send_at: this.viewDateTime(item.send_at),
				sent_at: this.viewDateTime(item.sent_at),
				recipient_count: item.recipient_count != null ? item.recipient_count : '-',
				created_at: this.viewDateTime(item.created_at),
			}));
		},

		getSetPage: {
			get() {
				return this.page;
			},
			set(page) {
				this.$emit('setPage', page);
			},
		},

		viewDateTime() {
			return date => formatDateTime(date);
		},

		getRecipientsActionTooltip() {
			return item => {
				switch (true) {
					case this.$_isBuilding(item) && this.$_isSending(item):
						return 'Pripravuje sa odoslanie';
					case this.$_isBuilding(item):
						return 'Prepočítava sa';
					case this.$_isSending(item):
						return 'Pozastaviť odosielanie';
					case this.$_isSendingStopped(item):
						return 'Pokračovať v odosielaní';
					case this.$_isSent(item):
						return 'Odoslaný';
					default:
						return 'Prepočítať počet adresátov';
				}
			};
		},
	},
};
</script>

<style lang="less">
[component='BannersList'] {
	.list {
		tbody {
			tr {
				cursor: pointer;
			}
		}
	}

	.progress-text {
		color: black;

		&.dark {
			color: white;
		}
	}
}
</style>
