<?php
abstract class Kohana_Controller_API extends OAuth2_Controller
{
	/**
	 * @var Object Request Payload
	 */
	protected $_request_payload = NULL;

	/**
	 * @var Object Response Payload
	 */
	protected $_response_payload = NULL;

	/**
	 * @var array Response Metadata
	 */
	protected $_response_metadata = array('error' => FALSE);

	/**
	 * @var array Response Links
	 */
	protected $_response_links = array();

	/**
	 * @var array Map of HTTP methods -> actions
	 */
	protected $_action_map = array
	(
		Http_Request::POST   => 'post',   // Typically Create..
		Http_Request::GET    => 'get',
		Http_Request::PUT    => 'put',    // Typically Update..
		Http_Request::DELETE => 'delete',
		'PATCH'              => 'patch',
	);

	/**
	 * @var array List of HTTP methods which support body content
	 */
	protected $_methods_with_body_content = array
	(
		Http_Request::POST,
		Http_Request::PUT,
		'PATCH'
	);

	/**
	 * @var array List of HTTP methods which may be cached
	 */
	protected $_cacheable_methods = array
	(
		Http_Request::GET,
	);

	public function before()
	{
		parent::before();

		$this->_parse_request();
	}

	public function after()
	{
		$this->_prepare_response();

		parent::after();
	}

	/**
	 * Parse the request...
	 */
	protected function _parse_request()
	{
		// Override the method if needed.
		$this->request->method(Arr::get(
			$_SERVER,
			'HTTP_X_HTTP_METHOD_OVERRIDE',
			$this->request->method()
		));

		// Is that a valid method?
		if ( ! isset($this->_action_map[$this->request->method()]))
		{
			// TODO .. add to the if (maybe??) .. method_exists($this, 'action_'.$this->request->method())
			throw new Http_Exception_405('The :method method is not supported. Supported methods are :allowed_methods', array(
				':method'          => $method,
				':allowed_methods' => implode(', ', array_keys($this->_action_map)),
			));
		}

		// Are we be expecting body content as part of the request?
		if (in_array($this->request->method(), $this->_methods_with_body_content))
		{
			$this->_parse_request_body();
		}
	}

	/**
	 * @todo Support more than just JSON
	 */
	protected function _parse_request_body()
	{
		if ($this->request->body() == '')
			return;

		try
		{
			$this->_request_payload = json_decode($this->request->body(), TRUE);

			if ( ! is_array($this->_request_payload) AND ! is_object($this->_request_payload))
				throw new Http_Exception_400('Invalid json supplied. \':json\'', array(
					':json' => $this->request->body(),
				));
		}
		catch (Exception $e)
		{
			throw new Http_Exception_400('Invalid json supplied. \':json\'', array(
				':json' => $this->request->body(),
			));
		}
	}

	protected function _prepare_response()
	{
		// Should we prevent this request from being cached?
		if ( ! in_array($this->request->method(), $this->_cacheable_methods))
		{
			$this->response->headers('cache-control', 'no-cache, no-store, max-age=0, must-revalidate');
		}

		$this->_prepare_response_body();
	}

	/**
	 * @todo Support more than just JSON
	 */
	protected function _prepare_response_body()
	{
		try
		{
			// Set the correct content-type header
			$this->response->headers('Content-Type', 'application/json');

			$response = array (
				'metadata' => $this->_response_metadata,
				'links'    => $this->_response_links,
				'payload'  => $this->_response_payload
			);

			// Format the reponse as JSON
			$this->response->body(json_encode($response));
		}
		catch (Exception $e)
		{
			Kohana::$log->add(Log::ERROR, 'Error while formatting response: '.$e->getMessage());
			throw new Http_Exception_500('Error while formatting response');
		}
	}

	/**
	 * Execute the API call..
	 */
	public function action_index()
	{
		// Get the basic verb based action..
		$action = $this->_action_map[$this->request->method()];

		// If this is a custom action, lets make sure we use it.
		if ($this->request->param('custom', FALSE) !== FALSE)
		{
			$action .= '_'.$this->request->param('custom');
		}

		// If we are acting on a collection, append _collection to the action name.
		if ($this->request->param('id', FALSE) === FALSE)
		{
			$action .= '_collection';
		}

		// Execute the request
		if (method_exists($this, $action))
		{
			try
			{
				$this->_execute($action);
			}
			catch (Exception $e)
			{
				$this->response->status(500);
				$this->_response_payload = NULL;
			}
		}
		else
		{
			/**
			 * @todo .. HTTP_Exception_405 is more approperiate, sometimes.
			 * Need to figure out a way to decide which to send...
			 */
			throw new HTTP_Exception_404('The requested URL :uri was not found on this server.', array(
				':uri' => $this->request->uri()
			));
		}
	}

	protected function _execute($action)
	{
		try
		{
			$this->{$action}();
		}
		catch (HTTP_Exception $e)
		{
			$this->response->status($e->getCode());

			$this->_response_metadata = array(
				'error' => TRUE,
				'type' => 'http',
			);

			$this->_response_payload = array(
				'message' => $e->getMessage(),
				'code'    => $e->getCode(),
			);

			$this->_response_links = array();
		}
		catch (Exception $e)
		{
			$this->response->status(500);

			$this->_response_metadata = array(
				'error' => TRUE,
				'type' => 'exception',
			);

			$this->_response_payload = array(
				'message' => $e->getMessage(),
				'code'    => $e->getCode(),
			);

			$this->_response_links = array();
		}
	}

	protected function _generate_link($method, $uri, $type, $parameters = NULL)
	{
		$link = array(
			'method'     => $method,
			'url'        => $uri,
			'type'       => $type,
			'parameters' => array(),
		);

		if ($parameters !== NULL)
		{
			foreach ($parameters as $search => $replace)
			{
				if (is_numeric($search))
				{
					$link['parameters'][':'.$replace] = $replace;
				}
				else
				{
					$link['parameters'][$search] = $replace;
				}
			}
		}

		return $link;
	}
}