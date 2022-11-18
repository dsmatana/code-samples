<template>
	<div component="NewslettersContentSource">
		<v-tooltip top>
			<template v-slot:activator="{ on }">
				<v-btn class="mr-2" v-on="on" @click="open" :small="$attrs['button-small']"
					>Zdroj<v-icon class="ml-2">mdi-xml</v-icon></v-btn
				>
			</template>
			<span>Zobraziť zdroj obsahu</span>
		</v-tooltip>
		<v-dialog content-class="source-dialog" v-model="dialog" max-width="80%" fullscreen>
			<v-card class="source-dialog-card">
				<v-card-title class="headline">Zdroj obsahu</v-card-title>
				<v-card-text class="source-dialog-card-text pb-0">
					<v-form ref="form">
						<v-textarea v-model="form.data.source" height="100%" :readonly="disabled" filled auto-grow></v-textarea>
					</v-form>
				</v-card-text>
				<v-card-actions>
					<v-spacer></v-spacer>
					<v-btn text @click="close">Zrušiť</v-btn>
					<v-btn color="primary" text @click="save" :disabled="disabled">Uložiť</v-btn>
				</v-card-actions>
			</v-card>
		</v-dialog>
	</div>
</template>

<script>
const defaultFormData = {
	source: '',
};

export default {
	name: 'NewslettersContentSource',

	props: {
		value: {
			type: String,
		},
		disabled: {
			type: Boolean,
		},
	},

	data: () => ({
		dialog: false,
		form: {
			data: { ...defaultFormData },
		},
	}),

	methods: {
		open() {
			this.form.data.source = this.value;
			this.dialog = true;
		},

		close() {
			this.dialog = false;
		},

		reset() {
			this.form.data = { ...defaultFormData };
		},

		save() {
			this.$emit('input', this.form.data.source);
			this.$emit('save', this.form.data.source);
			this.close();
		},
	},

	watch: {
		dialog(newVal) {
			if (!newVal) {
				this.reset();
			}
		},
	},
};
</script>

<style lang="less" scoped>
/deep/ .source-dialog {
	overflow: hidden;
}

.source-dialog-card {
	display: flex;
	flex-direction: column;
	height: 100%;

	.source-dialog-card-text {
		height: 100%;

		form {
			height: 100%;

			.v-input,
			/deep/ .v-input__control,
			/deep/ .v-input__slot {
				height: 100%;
			}

			/deep/ textarea {
				height: calc(100% - 10px) !important;
			}
		}
	}
}
</style>
