<template>
	<div component="NewslettersFilter" class="d-flex flex-wrap">
		<v-col sm="12" md="4" lg="4">
			<date-range-picker v-model="getSetDateRange" label="Dátum odoslania Od Do" clearable filled />
			<v-select
				v-model="filter.segment"
				:items="getSegmentsList"
				label="Segment"
				item-value="id"
				item-text="title"
				multiple
				filled
				clearable
				@change="onChange"
			></v-select>
		</v-col>
		<v-col sm="12" md="4" lg="4">
			<v-select
				v-model="filter.topic"
				:items="getNewslettersTopics"
				label="Kanál"
				item-value="id"
				item-text="title"
				multiple
				filled
				clearable
				@change="onChange"
			></v-select>
			<v-select v-model="filter.state" :items="getStateItems" label="Stav" @change="onChange" filled></v-select>
		</v-col>
		<v-col sm="12" md="4" lg="4">
			<v-btn
				:disabled="!isFilterDirty"
				:color="isFilterDirty ? 'primary' : undefined"
				@click="reset"
				:rounded="false"
				icon
				x-large
				outlined
			>
				<v-icon>mdi-delete-forever</v-icon>
			</v-btn>
		</v-col>
	</div>
</template>

<script>
import { mapGetters } from 'vuex';
import { clone } from '@/helpers';

import { defaultFilters, newsletterStates } from '@/store/newsletters';

export default {
	data: () => ({
		filter: {},

		dateRangePickerOpen: false,
	}),

	created() {
		this.filter = clone(this.getNewslettersFilter);
	},

	methods: {
		reset() {
			this.filter = clone(defaultFilters);
			this.onChange();
		},

		onChange() {
			this.$emit('change', this.filter);
		},
	},

	computed: {
		...mapGetters(['getNewslettersFilter', 'getNewslettersTopics', 'getSegmentsList']),

		getStateItems() {
			return [
				{ text: 'Všetky', value: null },
				...Object.entries(newsletterStates).map(([key, i]) => ({ text: i.title, value: key })),
			];
		},

		getSetDateRange: {
			get() {
				return [this.filter.sentAtFrom, this.filter.sentAtTo].reduce((result, value) => {
					if (value) {
						result.push(value);
					}
					return result;
				}, []);
			},
			set(range) {
				this.filter.sentAtFrom = range[0] || undefined;
				this.filter.sentAtTo = range[1] || undefined;
				this.onChange();
			},
		},

		isFilterDirty() {
			return JSON.stringify(this.getNewslettersFilter) != JSON.stringify(defaultFilters);
		},
	},
};
</script>
