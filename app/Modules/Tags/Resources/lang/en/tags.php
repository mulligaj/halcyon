<?php
return [
	'module name' => 'Tag Manager',
	'id' => 'ID',
	'name' => 'Tag',
	'tagged' => 'Tagged',
	'description' => 'Description',
	'alias' => 'Aliases',
	'alias hint' => 'Enter a comma-separated list of alternate spellings, abbreviations, or synonyms for this tag.',
	'slug' => 'Slug',
	'slug placeholder' => 'This is a read-only field that will be filled in after saving.',
	'slug hint' => 'To create the slug (used for URLs), all spaces, punctuation, and non-alpanumeric characters are stripped. "N.Y.", "NY", and "ny" will all have a normalized tag of "ny".',
	'created' => 'Created',
	'FIELD_CREATOR' => 'Creator',
	'FIELD_MODIFIER' => 'Modifier',
	'FIELD_MODIFIED' => 'Modified',
	'tag id' => 'Tag ID',
	'tag id hint' => 'The ID of the tag being linked.',
	'taggable id' => 'Item ID',
	'taggable id hint' => 'The ID of the item being tagged.',
	'taggable type' => 'Item type',
	'taggable type hint' => 'The type of item being tagged. This typically corresponds to a module name such as "user", "page", etc.',
	'aliases' => 'Aliases',
	'history' => 'History',
	'record id' => 'Tag #:id',
	'item removed' => 'Item removed.',
	'item added' => 'Item added.',
	'domain' => 'Namespace',
	'domain hint' => 'A namespace that allows for limiting queries to specific tag sets. This is typically a module name, such as "users" for tags associated with user profiles.',
];