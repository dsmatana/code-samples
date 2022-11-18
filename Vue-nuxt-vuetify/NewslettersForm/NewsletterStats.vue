<template>
	<v-card component="NewsletterStats">
		<v-card-title>
			<span>Štatistiky</span>
			<v-spacer></v-spacer>

			<v-tooltip top>
				<template v-slot:activator="{ on }">
					<div v-on="on">
						<v-btn
							v-if="!exporting"
							:disabled="$_isBuilding() || !$_isUpdatableAndExists()"
							@click.stop="$emit('export')"
							icon
						>
							<v-icon>mdi-upload</v-icon>
						</v-btn>
						<v-progress-circular v-else class="mr-2" color="primary" :size="23" indeterminate></v-progress-circular>
					</div>
				</template>
				<span>Exportovať zoznam adresátov</span>
			</v-tooltip>

			<v-tooltip top>
				<template v-slot:activator="{ on }">
					<div class="ml-2" v-on="on">
						<v-btn
							v-if="!$_isBuilding()"
							@click.stop="$emit('build')"
							:disabled="$_isBuilding() || !$_isUpdatableAndExists()"
							icon
						>
							<v-icon class="text--secondary">mdi-refresh</v-icon>
						</v-btn>
						<v-progress-circular v-else class="mr-3" color="primary" :size="23" indeterminate></v-progress-circular>
					</div>
				</template>
				<span>Prepočítať zoznam adresátov</span>
			</v-tooltip>
		</v-card-title>
		<v-card-text>
			<v-row class="stats">
				<v-col>
					<div class="text-caption text--secondary"># adresátov</div>
					<div class="text-h5">{{ getCount('recipient_count') }}</div>
				</v-col>
				<v-col>
					<div class="text-caption text--secondary"># impresií</div>
					<div class="text-h5">{{ getCount('impressions_count') }}</div>
				</v-col>
				<v-col>
					<div class="text-caption text--secondary"># kliknutí</div>
					<div class="text-h5">{{ getCount('clicks_count') }}</div>
				</v-col>
			</v-row>
		</v-card-text>
	</v-card>
</template>

<script>
import newsletters from '@/mixins/newsletters';

export default {
	name: 'NewsletterStats',

	mixins: [newsletters],

	props: {
		newsletter: {
			type: Object | undefined,
			required: true,
		},
		exporting: {
			type: Boolean,
			default: false,
		},
	},

	computed: {
		getCount() {
			return prop => (this.newsletter && this.newsletter[prop] != null ? this.newsletter[prop] : '-');
		},
	},
};
</script>

<style lang="less" scoped>
[component='NewsletterStats'] {
	.stats {
		/deep/ .col {
			display: flex;
			flex-direction: column;
			align-items: center;
		}
	}
}
</style>
