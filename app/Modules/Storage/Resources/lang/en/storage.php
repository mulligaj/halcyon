<?php
return [
	'module name' => 'Storage Manager',
	'module sections' => 'Module sections',
	'storage' => 'Storage',
	'resource' => 'Resource',

	// Columns
	'id' => 'ID',
	'name' => 'Name',
	'name desc' => 'Lowercase alpha-numeric characters, dashes, underscores. Ex: foo_bar',
	'path' => 'Path',
	'path desc' => 'The base file path where sub-directories will be created. Ex: /scratch/example',
	'quota space' => 'Space Quota',
	'quota file' => 'File Quota',
	'quota file desc' => 'The default max number of files a space can have. Set to zero for unlimited.',
	'active' => 'Active',
	'created' => 'Created',
	'creator' => 'Creator',
	'removed' => 'Removed',
	'directories' => 'Directories',
	'quota' => 'Quota',
	'owner' => 'Owner',
	'group' => 'Group',
	'import' => 'Import',
	'import desc' => 'Import information from ...',
	'import hostname' => 'Import hostname',
	'get quota type' => '"get quota" message',
	'get quota type desc' => 'Select the Message Queue type for a "get quota" command.',
	'create type' => '"make directory" message',
	'create type desc' => 'Select the Message Queue type for a "make directory" command.',
	'message queue' => 'Message Queue',
	'notification types' => 'Notification Types',
	'notifications' => 'Notifications',
	'time period' => 'Default time period',
	'value type' => 'Value type',
	'percent' => 'Percentage',
	'number' => 'Number',
	'bytes' => 'Bytes',
	'no storage found' => 'No storage items found for this group.',

	// Misc.
	'all resources' => '- All Resources -',
	'all storage resources' => '- All Storage Resources -',
	'active' => 'Active',
	'inactive' => 'Inactive',

	// Errors
	'error' => [
		'no items selected' => 'No entry selected',
		'select items' => 'Select an entry to :action',
		'invalid parent' => 'Please select a parent resource.',
		'invalid name' => 'Please provide a name.',
		'directories found using unix group' => '{1} :num directory found using this unix group. Re-assign or remove directory first.|[2,*] :num directories found using this unix group. Re-assign or remove directory first.',
	],

	// Fields
	'role name' => 'Role Name',
	'list name' => 'List Name',
	'resource type' => 'Resource',
	'product type' => 'Product',
	'parent' => 'Parent Resource',
	'quota desc' => 'Size in bytes. Use size abbreviations (PB, TB, GB, KB, MB, B). Values with no abbreviation will be taken as bytes (ex: 100000 = 100000 B).',
	'quota space desc' => 'Size in bytes. Use size abbreviations (PB, TB, GB, KB, MB, B). Values with no abbreviation will be taken as bytes (ex: 100000 = 100000 B).',
	'my quotas' => 'Storage Quotas',
	'messages' => 'Queued Processes',
	'autouserdir' => 'Auto-create user directories',
	'autouserdir desc' => 'This indicates if directories for users should be auto-created.',
	'status' => 'Status',
	'action' => 'Action',
	'submitted' => 'Submitted',
	'completed' => 'Completed',
	'runtime' => 'Runtime',
	'group' => 'Group',
	'permissions' => 'Permissions',
	'fix permissions' => 'Fix File Permissions',
	'permission' => [
		'level' => 'Level',
		'owner' => 'Owner',
		'group' => 'Group',
		'public' => 'Public',
		'read' => 'Read',
		'write' => 'Write',
		'execute' => 'Execute',
	],
	'permissions type' => [
		'group shared' => 'Group Shared',
		'auto user group readable' => 'Auto User - Group Readable',
		'auto user group writeable' => 'Auto User - Group Readable & Writeable',
		'auto user private' => 'Auto User - Private',
		'user owned readable' => 'User Owned - Group Readable',
		'user owned writeable' => 'User Owned - Group Writeable',
		'user owned private' => 'User Owned - Private',
	],
	'permissions type desc' => '"Auto User" values will auto-create directories for anyone assigned to a "Populating Unix Group".',
	'future quota' => 'Future Quota Changes',
	'unallocated space' => 'Unallocated space',
	'remove overallocated' => 'Remove over-allocated space from this directory',
	'distribute remaining' => 'Distribute remaining space',
	'access unix group' => 'Access Unix Group',
	'populating unix group' => 'Populating Unix Group',
	'select unix group' => '(Select Unix Group)',

	// Purchases/Loans
	'type' => 'Type',
	'comment' => 'Comment',
	'start' => 'Starts',
	'end' => 'Ends',
	'amount' => 'Amount',
	'total' => 'Total',
	'history' => 'History',
	'source' => 'From/To',
	'sell space' => 'Sell Space',
	'loan space' => 'Loan Space',
	'end of life' => '(end of system life)',
	'select group' => '(select group)',
	'org owned' => '(Organization Owned)',
	'seller' => 'Seller',
	'lender' => 'Lender',
	'sell to' => 'Sell to',
	'loan to' => 'Loan to',
	'options' => 'Options',
	'edit purchase' => 'Edit purchase',
	'edit loan' => 'Edit loan',
	'mailquota' => [
		'exceed' => 'Quota Usage Alert',
		'below' => 'Quota Usage Alert',
		'report' => 'Quota Usage Report',
	],
	'available space' => 'Available space',
	'group managed' => 'Group managed spaces',
	'group managed desc' => 'Group managers can create directories and apply unix groups & permissions.',
	'add new directory' => 'Add New Directory',
];
