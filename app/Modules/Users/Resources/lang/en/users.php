<?php
return [
	'module name' => 'User Manager',
	'module sections' => 'Module sections',
	'users' => 'Users',
	'notes' => 'Notes',
	'roles' => 'Roles',
	'levels' => 'Access Levels',
	// Fields
	'id' => 'ID',
	'name' => 'Name',
	'first name' => 'First name',
	'middle name' => 'Middle name',
	'last name' => 'Last name',
	'username' => 'Username',
	'email' => 'Email',
	'blocked' => 'Blocked',
	'sign in welcome message' => 'Welcome!',
	'last visit' => 'Last Visit',
	'registered' => 'Registered',
	'assigned roles' => 'Assigned Roles',
	'back' => 'Back',
	// Status
	'status' => 'Status',
	'status trashed' => 'Trashed',
	'status disabled' => 'Suspended',
	'status enabled' => 'Enabled',
	'status pending' => 'Pending',
	'status unapproved' => 'Not Approved',
	'status unconfirmed' => 'Not Verified',
	'status approved details' => 'Email confirmed. Account approved.',
	'status unapproved details' => 'Email confirmed but account requires approval.',
	'status unconfirmed details' => 'The email address is unconfirmed.',
	'status blocked details' => 'This account is blocked.',
	'status incomplete' => 'Incomplete (%s)',
	'status incomplete details' => 'Registration via 3rd-party authenticator but hasn\'t completed the process.',
	// Actions
	'trashed on' => 'Removed on :date',
	'disable this user' => 'Suspend this account',
	'disable' => 'Suspend',
	'enable' => 'Enable',
	'unconfirm email' => 'Unconfirm email',
	'confirm email' => 'Confirm email',
	'resend confirmation' => 'Resend confirmation',
	'email awaiting confirmation' => 'Email awaiting confirmation',
	'authenticator' => 'Authenticator',
	// Filters
	'all states' => '- All States -',
	'enabled' => 'Enabled',
	'disabled' => 'Suspended',
	'user enabled' => ':count users(s) enabled',
	'user disabled' => ':count users(s) suspended',
	'select approved status' => '- Approved -',
	'unapproved' => 'Unapproved',
	'manually approved' => 'Manually approved',
	'automatically approved' => 'Automatically Approved',
	'all roles' => '- All Roles -',
	'registration date' => 'Registration date',
	'select registration date' => '- Registration date -',
	'range today' => 'Today',
	'range past week' => 'In the last week',
	'range past month' => 'In the last month',
	'range past 3 months' => 'In the last 3 months',
	'range past 6 months' => 'In the last 6 months',
	'range past year' => 'In the last year',
	'range post year' => 'More than a year ago',
	'not reviewed' => '(not reviewed)',
	'subject' => 'Subject',
	'reviewed' => 'Reviewed',
	'register ip' => 'Register IP',
	'register date' => 'Registered',
	'last visit date' => 'Last Visit',
	'last modified date' => 'Last Modified Date',
	'debug user' => 'Debug user',
	'select module' => '- Select Module -',
	'select level end' => '- Select End Level -',
	'select level start' => '- Select Start Level -',
	'organization id' => 'Organization ID',
	'email verified at' => 'Email verified @ :datetime',
	'visible for roles' => 'Visible for roles',
	'permissions' => 'Permissions',
	'api token' => 'API token',
	'regenerate' => 'Regenerate',
	// Site
	'my account' => 'Account',
	'request access' => 'Request Access',
	'sourced description' => 'Some fields are populated by a 3rd-party service and cannot be edited.',
	'api token hint' => 'This is a randomly generated token for authenticating with the API.',
	'profile' => 'Profile',
	'online' => 'Online',
	// Attributes
	'attributes' => 'Attributes',
	'key' => 'Key',
	'value' => 'Value',
	'access' => 'Access',
	'private' => 'Private',
	'locked' => 'Locked',
	'sessions' => 'Sessions',
	'invalid' => [
		'username' => 'Invalid username.',
		'name' => 'Invalid name.',
	],
	'error' => [
		'user not found' => 'User not found.',
		'role set failed' => 'Failed to set roles for user :username',
		'username taken' => 'User with the specified username alreaady exists.',
		'email taken' => 'User with the specified email alreaady exists.',
	],
	'user created' => 'User created.',
	'department' => 'Department',
	'title' => 'Title',
	'campus' => 'Campus',
	'phone' => 'Phone',
	'building' => 'Building',
	'room' => 'Room',
	'this account was removed on date' => 'This account was removed on :date',
	// Delete account
	'delete' => [
		'delete account' => 'Delete Account',
		'description' => 'Once you delete your account, this cannot be undone. Please be certain.',
		'are you sure' => 'Are you sure?',
		'confirm' => 'Please type "<strong>:username</strong>" to confirm.',
		'confirm submit' => 'I understand the consequences, delete this account',
		'warning' => 'This action cannot be undone. This will permanently delete the account and remove all associations.',
	],
	'account suspended' => 'This account has been suspended.',
	'welcome' => 'Welcome!',
	'account expired' => 'Account Expired',
];