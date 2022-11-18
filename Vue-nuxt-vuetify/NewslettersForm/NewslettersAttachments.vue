<template>
	<div component="NewslettersAttachments">
		<v-card>
			<v-card-title class="pb-0"
				>Prílohy
				<span v-if="!!maxCount && newsletter.attachments.length" class="text-caption text--secondary">
					&nbsp;({{ newsletter.attachments.length }}/{{ maxCount }})
				</span>
			</v-card-title>
			<v-card-text class="pa-0">
				<v-list class="pa-0" v-if="newsletter.attachments.length" dense>
					<v-list-item v-for="item in newsletter.attachments" :key="item.id" two-line>
						<v-list-item-content>
							<div class="d-flex w-100">
								<v-list-item-title>
									<v-icon>mdi-file-pdf-box</v-icon>
									<a :href="getAttachmentUrl(item)" target="_blank">{{ item.name }}</a>
								</v-list-item-title>
								<v-btn icon :disabled="disabled" @click="removeItemData = { confirm: true, item }">
									<v-icon>mdi-close</v-icon>
								</v-btn>
							</div>
							<v-list-item-subtitle>Veľkosť: {{ item.size_readable }}</v-list-item-subtitle>
						</v-list-item-content>
					</v-list-item>
				</v-list>
			</v-card-text>
			<v-card-actions>
				<v-btn color="primary" :loading="$_stateIs(states.uploading)" :disabled="!canUpload" @click="open" text>
					Pridať
				</v-btn>
				<span class="text-caption text--secondary" v-if="recordCreated">(Max. 10MB)</span>
				<span class="text-caption text--secondary" v-else>&nbsp;Dostupné po uložení</span>
			</v-card-actions>
		</v-card>

		<input type="file" id="attachmentInput" ref="attachmentInput" accept="application/pdf" @change="upload()" hidden />

		<v-dialog v-model="removeItemData.confirm" max-width="290">
			<v-card>
				<v-card-title class="headline">Potvrdiť</v-card-title>
				<v-card-text>Vymazať prílohu ?</v-card-text>
				<v-card-actions>
					<v-spacer></v-spacer>
					<v-btn color="primary" text @click="removeItemData.confirm = false">Zrušiť</v-btn>
					<v-btn
						color="primary"
						text
						@click="
							removeItemData.confirm = false;
							_removeNewsletterAttachment();
						"
						>OK</v-btn
					>
				</v-card-actions>
			</v-card>
		</v-dialog>
	</div>
</template>

<script>
import { mapActions, mapMutations } from 'vuex';

const ONEMB = 1048576;
const MAX_SIZE = ONEMB * 10;

const PDF_VALIDATION_ERROR = `Maximálna veľkost PDF Prílohy je ${MAX_SIZE / 1024 / 1024}MB`;

const states = {
	idle: 'idle',
	uploading: 'uploading',
};

export default {
	name: 'NewslettersAttachments',

	props: {
		newsletter: {
			type: Object,
		},
		maxCount: {
			type: Number,
		},
		disabled: {
			type: Boolean,
		},
	},

	data: () => ({
		states,
		state: states.idle,
		messages: [],
		error: false,
		removeItemData: {
			confirm: false,
			item: null,
		},
		// prettier-ignore
		rules: {
			//Do not use in vuetify (custom)
			attachment: [
				(v) => !!!v && true || v.size < MAX_SIZE || PDF_VALIDATION_ERROR
			]
		},
	}),

	methods: {
		...mapActions(['storeNewsletterAttachment', 'removeNewsletterAttachment']),
		...mapMutations(['openSnackbar']),

		open() {
			this.attachmentInput.click();
		},

		async upload() {
			this.validate();

			if (this.error) {
				this.openSnackbar({ message: this.messages.join('<br/>'), color: 'error' });
				return;
			}

			this.validateReset();
			this.$emit('upload:start');

			const file = this.attachmentInput.files[0];
			const formData = new FormData();
			formData.append('file', file);

			try {
				this.$_setState(states.uploading);
				await this.storeNewsletterAttachment({ newsletter: this.newsletter, formData });
				this.validateReset();
			} catch (e) {
				this.messages = [e];
				this.error = true;
				if (e.response?.data?.errors?.file[0]) {
					e = PDF_VALIDATION_ERROR;
				}
				this.openSnackbar({ message: e, color: 'error' });
			} finally {
				this.attachmentInput.value = '';
				this.$emit('upload:complete');
				this.$emit('change', this.newsletter.attachments);
				this.$_setState(states.idle);
			}
		},

		validate(markError = true) {
			this.validateReset();
			const file = this.attachmentInput.files[0];

			this.messages = this.rules.attachment.reduce((result, fn) => {
				const validationResult = fn(file, this);
				validationResult !== true && result.push(validationResult);
				return result;
			}, []);

			this.error = markError && this.messages.length > 0;
		},

		validateReset() {
			this.messages = [];
			this.error = false;
		},

		async _removeNewsletterAttachment() {
			try {
				await this.removeNewsletterAttachment(this.removeItemData.item);
				this.openSnackbar({ message: 'Záznam bol vymazaný' });
			} catch (error) {
				this.openSnackbar({ message: error, color: 'error' });
			} finally {
				this.removeItemData = { confirm: false, item: null };
				this.$emit('change', this.newsletter.attachments);
			}
		},
	},

	computed: {
		attachmentInput() {
			return this.$refs.attachmentInput;
		},

		getAttachmentUrl() {
			return item => `${process.env.api_endpoint}/newsletters-attachments/${item.uuid}`;
		},

		recordCreated() {
			return !!this.newsletter.id;
		},

		canUpload() {
			return (
				this.$_stateNot(states.uploading) &&
				(this.maxCount ? this.newsletter.attachments.length < this.maxCount : true) &&
				!this.disabled
			);
		},
	},
};
</script>
