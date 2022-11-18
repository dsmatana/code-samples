<template>
	<div component="NewslettersContent">
		<!-- <ClientOnly> -->
		<TiptapVuetify
			v-model="getSetValue"
			:disabled="disabled"
			:extensions="tiptapExtensions"
			:toolbar-attributes="{ color: $vuetify.theme.dark ? 'text-black' : 'grey lighten-4' }"
		>
			<template v-slot:toolbar-after>
				<slot name="editor-toolbar-after"></slot>
			</template>
		</TiptapVuetify>
		<!-- </ClientOnly> -->
	</div>
</template>

<script>
import {
	Blockquote,
	Bold,
	BulletList,
	Code,
	CodeBlock,
	HardBreak,
	Heading,
	History,
	HorizontalRule,
	Image,
	Italic,
	Link,
	ListItem,
	OrderedList,
	Paragraph,
	Strike,
	Table,
	TableCell,
	TableHeader,
	TableRow,
	TiptapVuetify,
	TodoItem,
	TodoList,
	Underline,
} from 'tiptap-vuetify';

export default {
	components: { TiptapVuetify },

	props: {
		value: {
			type: String,
			required: true,
		},
		disabled: {
			type: Boolean,
		},
	},

	data: () => ({
		// declare extensions you want to use
		tiptapExtensions: [
			History,
			Bold,
			Italic,
			Underline,
			Strike,
			[
				Heading,
				{
					options: {
						levels: [1, 2, 3],
					},
				},
			],
			Link,
			OrderedList,
			BulletList,
			Blockquote,
			Code,
			CodeBlock,
			HardBreak,
			HorizontalRule,
			Image,
			ListItem,
			Paragraph,
			Table,
			TableCell,
			TableHeader,
			TableRow,
			// TodoItem, //not working
			// TodoList, //not working
		],
	}),

	computed: {
		getSetValue: {
			get() {
				return this.value;
			},
			set(value) {
				this.$emit('input', value);
			},
		},
	},
};
</script>

<style lang="less" scoped>
.ql-container {
	max-height: 240px;
	overflow-y: auto;
}
</style>
