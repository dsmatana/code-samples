<template>
	<div component="NewsletterTemplates" class="align-center d-flex flex-column justify-center">
		<h2>Vyberte šablónu</h2>
		<div class="d-flex">
			<v-card class="card ma-3" v-for="item in getNewslettersTemplates" :key="item.id" @click="$emit('select', item)">
				<v-card-title class="justify-center">{{ item.title }}</v-card-title>
				<v-card-text class="text-center">
					<v-icon class="text-h3">{{ item.icon }}</v-icon>
				</v-card-text>
			</v-card>
		</div>
	</div>
</template>

<script>
import { mapGetters } from 'vuex';

export default {
	name: 'NewsletterTemplates',
	computed: {
		...mapGetters(['getNewslettersTemplates']),
	},
};
</script>
<style lang="less" scoped>
[component='NewsletterTemplates'] {
	.card {
		width: 200px;
	}
}
</style>
