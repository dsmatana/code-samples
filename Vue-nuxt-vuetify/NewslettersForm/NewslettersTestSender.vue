<template>
	<div component="NewslettersTestSender">
		<v-tooltip top>
			<template v-slot:activator="{ on }">
				<v-btn class="mr-2" v-on="on" @click="open" :disabled="disabled"
					>Test <v-icon class="ml-2">mdi-email-fast</v-icon></v-btn
				>
			</template>
			<span>Testovacie odoslanie na email</span>
		</v-tooltip>
		<v-dialog v-model="dialog" max-width="400">
			<v-card>
				<v-card-title class="headline">Odoslať na email</v-card-title>
				<v-card-text>
					<v-form ref="form" v-model="form.valid" lazy-validation>
						<v-combobox
							v-model="form.data.recipients"
							label="Adresáti"
							hint="Zadajte email a stlačte TAB pre viac adresátov"
							:rules="form.validationRules.recipients"
							deletable-chips
							multiple
							chips
						></v-combobox>
					</v-form>
				</v-card-text>

				<v-card-actions>
					<v-spacer></v-spacer>
					<v-btn color="primary" text @click="close">Zrušiť</v-btn>
					<v-btn color="primary" text @click="send">Odoslať</v-btn>
				</v-card-actions>
			</v-card>
		</v-dialog>
	</div>
</template>

<script>
import { mapActions, mapMutations } from 'vuex';
import localStorage from 'local-storage';
import { validEmails } from '@/helpers';

const FORM_DATA_STORAGE_KEY = 'amos.newsletters.test-sender.form';

const defaultFormData = {
	recipients: [],
};

export default {
	name: 'NewslettersTestSender',

	props: {
		newsletter: {
			type: Object,
		},
		disabled: {
			type: Boolean,
		},
	},

	data: () => ({
		dialog: false,
		form: {
			valid: false,
			data: { ...defaultFormData },
			// prettier-ignore
			validationRules: {
				recipients: [
					v => !!v.length || 'Zadajte aspoň jedného adresáta',
					v => (v.length && validEmails(v)) || 'Zadajte validnych adresátov',
				],
			},
		},
	}),

	methods: {
		...mapActions(['sendNewsletterTest']),
		...mapMutations(['openSnackbar']),

		open() {
			this.form.data = { ...defaultFormData, ...(localStorage(FORM_DATA_STORAGE_KEY) || {}) };

			// Pre-fill user's email
			if (!this.form.data.recipients.length) {
				this.form.data.recipients = [this.$auth.user.email];
			}

			this.dialog = true;
		},

		close() {
			this.form.data = { ...defaultFormData };
			this.$refs.form.resetValidation();
			this.dialog = false;
		},

		async send() {
			if (!this.$refs.form.validate()) {
				return;
			}

			try {
				await this.sendNewsletterTest({ newsletter: this.newsletter, recipients: this.form.data.recipients });
				this.openSnackbar({ message: 'Newsletter bol odoslaný' });
			} catch (e) {
				this.openSnackbar({ message: e, color: 'error' });
			} finally {
				localStorage(FORM_DATA_STORAGE_KEY, this.form.data);
				this.close();
			}
		},
	},
};
</script>
