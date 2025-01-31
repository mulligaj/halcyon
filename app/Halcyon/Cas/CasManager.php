<?php

namespace App\Halcyon\Cas;

use phpCAS;

class CasManager
{
	/**
	 * Array for storing configuration settings.
	 *
	 * @var array<string,mixed>
	 */
	protected $config;

	/**
	 * Attributes used for overriding or masquerading.
	 *
	 * @var array<string,mixed>
	 */
	protected $attributes = [];

	/**
	 * Boolean flag used for masquerading as a user.
	 *
	 * @var bool
	 */
	protected $masquerading = false;

	/**
	 * Constructor
	 *
	 * @param array<string,mixed> $config
	 */
	public function __construct(array $config)
	{
		$this->parseConfig($config);

		if ($this->config['cas_debug'] === true)
		{
			try
			{
				phpCAS::setDebug();
			}
			catch (\Exception $e)
			{
				// Fix for depreciation of setDebug
				phpCAS::setLogger();
			}

			phpCAS::log('Loaded configuration:' . PHP_EOL . serialize($this->config));
		}

		phpCAS::setVerbose($this->config['cas_verbose_errors']);

		// Fix for PHP 7.2.  See http://php.net/manual/en/function.session-name.php
		if (!headers_sent() && session_id() == "")
		{
			session_name($this->config['cas_session_name']);

			// Harden session cookie to prevent some attacks on the cookie (e.g. XSS)
			session_set_cookie_params(
				$this->config['cas_session_lifetime'],
				$this->config['cas_session_path'],
				env('APP_DOMAIN'),
				env('HTTPS_ONLY_COOKIES'),
				$this->config['cas_session_httponly']
			);
		}

		$this->configureCas($this->config['cas_proxy'] ? 'proxy' : 'client');

		$this->configureCasValidation();

		// set login and logout URLs of the CAS server
		phpCAS::setServerLoginURL($this->config['cas_login_url']);

		// If specified, this will override the URL the user will be returning to.
		if ($this->config['cas_redirect_path'])
		{
			phpCAS::setFixedServiceURL($this->config['cas_redirect_path']);
		}

		phpCAS::setServerLogoutURL($this->config['cas_logout_url']);

		if ($this->config['cas_masquerade'])
		{
			$this->masquerading = true;

			phpCAS::log('Masquerading as user: ' . $this->config['cas_masquerade']);
		}
	}

	/**
	 * Configure CAS Client|Proxy
	 *
	 * @param string $method
	 * @return void
	 */
	protected function configureCas($method = 'client')
	{
		if ($this->config['cas_enable_saml'])
		{
			$server_type = SAML_VERSION_1_1;
		}
		else
		{
			// This allows the user to use 1.0, 2.0, etc as a string in the config
			$cas_version_str = 'CAS_VERSION_' . str_replace('.', '_', $this->config['cas_version']);

			// We pull the phpCAS constant values as this is their definition
			// PHP will generate a E_WARNING if the version string is invalid which is helpful for troubleshooting
			$server_type = constant($cas_version_str);

			if (is_null($server_type))
			{
				// This will never be null, but can be invalid values for which we need to detect and substitute.
				phpCAS::log('Invalid CAS version set; Reverting to defaults');
				$server_type = CAS_VERSION_2_0;
			}
		}

		phpCAS::$method(
			$server_type,
			$this->config['cas_hostname'],
			(int) $this->config['cas_port'],
			$this->config['cas_uri'],
			$this->config['cas_base_url'],
			$this->config['cas_control_session']
		);

		if ($this->config['cas_enable_saml'])
		{
			// Handle SAML logout requests that emanate from the CAS host exclusively.
			// Failure to restrict SAML logout requests to authorized hosts could
			// allow denial of service attacks where at the least the server is
			// tied up parsing bogus XML messages.
			phpCAS::handleLogoutRequests(true, explode(',', $this->config['cas_real_hosts']));
		}
	}

	/**
	 * Maintain backwards compatibility with config file
	 *
	 * @param array<string,mixed> $config
	 * @return void
	 */
	protected function parseConfig(array $config)
	{
		$defaults = [
			'cas_hostname'         => '',
			'cas_base_url'         => '',
			'cas_session_name'     => 'CASAuth',
			'cas_session_lifetime' => 7200,
			'cas_session_path'     => '/',
			'cas_control_session'  => false,
			'cas_session_httponly' => true,
			'cas_port'             => 443,
			'cas_uri'              => '/cas',
			'cas_validation'       => '',
			'cas_cert'             => '',
			'cas_proxy'            => false,
			'cas_validate_cn'      => true,
			'cas_login_url'        => '',
			'cas_logout_url'       => 'https://cas.myuniv.edu/cas/logout',
			'cas_logout_redirect'  => '',
			'cas_redirect_path'    => '',
			'cas_enable_saml'      => true,
			'cas_version'          => "2.0",
			'cas_debug'            => false,
			'cas_verbose_errors'   => false,
			'cas_masquerade'       => ''
		];

		if (!isset($config['cas_base_url']) || !$config['cas_base_url'])
		{
			$config['cas_base_url'] = request()->getSchemeAndHttpHost();
		}

		$this->config = array_merge($defaults, $config);
	}

	/**
	 * Configure SSL Validation
	 *
	 * Having some kind of server cert validation in production
	 * is highly recommended.
	 *
	 * @return void
	 */
	protected function configureCasValidation()
	{
		if ($this->config['cas_validation'] == 'ca'
		 || $this->config['cas_validation'] == 'self')
		{
			phpCAS::setCasServerCACert($this->config['cas_cert'], $this->config['cas_validate_cn']);
		}
		else
		{
			// Not safe (does not validate your CAS server)
			phpCAS::setNoCasServerValidation();
		}
	}

	/**
	 * Authenticates the user based on the current request.
	 *
	 * @return bool
	 */
	public function authenticate()
	{
		if ($this->isMasquerading())
		{
			return true;
		}

		return phpCAS::forceAuthentication();
	}

	/**
	 * Returns the current config.
	 *
	 * @return array<string,mixed>
	 */
	public function getConfig()
	{
		return $this->config;
	}

	/**
	 * Retrieve authenticated credentials.
	 * Returns either the masqueraded account or the phpCAS user.
	 *
	 * @return string
	 */
	public function user()
	{
		if ($this->isMasquerading())
		{
			return $this->config['cas_masquerade'];
		}

		return phpCAS::getUser();
	}

	/**
	 * Retrieve current user.
	 *
	 * @return string
	 */
	public function getCurrentUser()
	{
		return $this->user();
	}

	/**
	 * Retrieve a specific attribute by key name.  The
	 * attribute returned can be either a string or
	 * an array based on matches.
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function getAttribute($key)
	{
		if (! $this->isMasquerading())
		{
			return phpCAS::getAttribute($key);
		}

		if ($this->hasAttribute($key))
		{
			return $this->attributes[$key];
		}

		return;
	}

	/**
	 * Check for the existence of a key in attributes.
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function hasAttribute($key)
	{
		if ($this->isMasquerading())
		{
			return array_key_exists($key, $this->attributes);
		}

		return phpCAS::hasAttribute($key);
	}

	/**
	 * Logout of the CAS session and redirect users.
	 *
	 * @param string $url
	 * @param string $service
	 * @return void
	 */
	public function logout($url = '', $service = '')
	{
		if (phpCAS::isSessionAuthenticated())
		{
			if (isset($_SESSION['phpCAS']))
			{
				$serialized = serialize($_SESSION['phpCAS']);
				phpCAS::log('Logout requested, but no session data found for user:' . PHP_EOL . $serialized);
			}
		}

		$params = [];

		if ($service)
		{
			$params['service'] = $service;
		}
		elseif ($this->config['cas_logout_redirect'])
		{
			$params['service'] = $this->config['cas_logout_redirect'];
		}

		if ($url)
		{
			$params['url'] = $url;
		}

		phpCAS::logout($params);
		exit;
	}


	/**
	 * Logout the user using the provided URL.
	 *
	 * @param string $url
	 * @return void
	 */
	public function logoutWithUrl($url)
	{
		$this->logout($url);
	}

	/**
	 * Get the attributes for for the currently connected user. This method
	 * can only be called after authenticate() or an error wil be thrown.
	 *
	 * @return mixed
	 */
	public function getAttributes()
	{
		// We don't error check because phpCAS has its own error handling.
		return $this->isMasquerading()
			? $this->attributes
			: phpCAS::getAttributes();
	}

	/**
	 * Checks to see is user is authenticated locally
	 *
	 * @return bool
	 */
	public function isAuthenticated()
	{
		return $this->isMasquerading() ? true : phpCAS::isAuthenticated();
	}

	/**
	 * Checks to see is user is globally in CAS
	 *
	 * @return bool
	 */
	public function checkAuthentication()
	{
		return $this->isMasquerading() ? true : phpCAS::checkAuthentication();
	}

	/**
	 * Checks to see if masquerading is enabled
	 *
	 * @return bool
	 */
	public function isMasquerading()
	{
		return $this->masquerading;
	}

	/**
	 * Set the attributes for a user when masquerading. This
	 * method has no effect when not masquerading.
	 *
	 * @param array $attr : the attributes of the user.
	 * @return void
	 */
	public function setAttributes(array $attr)
	{
		$this->attributes = $attr;

		phpCAS::log('Forced setting of user masquerading attributes: ' . serialize($attr));
	}

	/**
	 * Pass through undefined methods to phpCAS
	 *
	 * @param string $method
	 * @param array $params
	 *
	 * @return mixed
	 * @throws \BadMethodCallException
	 */
	public function __call($method, $params)
	{
		if (method_exists('phpCAS', $method)
		 && is_callable(['phpCAS', $method]))
		{
			return call_user_func_array(['phpCAS', $method], $params);
		}

		throw new \BadMethodCallException('Method not callable in phpCAS ' . $method . '::' . print_r($params, true));
	}
}
