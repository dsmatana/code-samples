<template>
	<div component="NewslettersContentHistory">
		<v-menu v-model="menu" transition="scale-transition" offset-y>
			<template v-slot:activator="{ on, attrs }">
				<div class="d-flex mr-2">
					<v-btn v-on="on" v-bind="attrs" :disabled="disabled" class="rounded-tr-0 rounded-br-0"
						>História<v-icon class="ml-2">mdi-history</v-icon></v-btn
					>
					<v-tooltip top>
						<template v-slot:activator="{ on, attrs }">
							<v-btn
								v-on="on"
								v-bind="attrs"
								color="primary"
								:disabled="!restorable"
								class="rounded-tl-0 rounded-bl-0"
								min-width="42"
								@click="$emit('restore')"
								><v-icon>mdi-restore</v-icon></v-btn
							>
						</template>
						<span>Reset</span>
					</v-tooltip>
				</div>
			</template>
			<v-list :disabled="disabled">
				<v-list-item v-for="item in historyItemList" :key="item.id" @click.stop="selectItem(item)">
					<v-list-item-title> {{ viewDateTime(item.created_at) }} </v-list-item-title>
				</v-list-item>
				<div v-if="!historyItemList.length" class="px-3">Žiadne položky</div>
			</v-list>
		</v-menu>
	</div>
</template>

<script>
import { formatDateTime } from '@/helpers';

export default {
	name: 'NewslettersContentHistory',

	props: {
		newsletter: {
			type: Object,
			required: true,
		},
		disabled: {
			type: Boolean,
		},
		restorable: {
			type: Boolean,
		},
	},

	data: () => ({
		menu: false,
	}),

	methods: {
		selectItem(historyItem) {
			if (historyItem.id) {
				this.$emit('input', { ...historyItem });
			} else {
				this.$emit('input', null);
				this.menu = false;
			}
			this.$emit('select', { ...historyItem });
		},
	},

	computed: {
		viewDateTime() {
			return date => formatDateTime(date);
		},
		historyItemList() {
			// Sort by created_at
			const items = [...this.newsletter.content_history].sort(function (a, b) {
				return new Date(b.created_at) - new Date(a.created_at);
			});
			// Remove first item
			items.splice(0, 1);
			return items;
		},
	},
};
</script>
