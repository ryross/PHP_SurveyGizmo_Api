<?php
namespace SurveyGizmo;

use SurveyGizmo\ApiResponse;

class ApiRequest
{

	private $baseuri = 'app.garrett.devo.boulder.sgizmo.com/services/rest/v5';
	private $request_return,
		$method,
		$page,
		$limit;

	public function __construct($method = "GET")
	{
		$this->method = $method;
	}

	public function makeRequest()
	{
		try {
			//get creds
			$creds = SurveyGizmoAPI::getAuth();
			$this->uri = $this->buildURI($creds);
			// TODO: look at moving to guzzle at some point
			if (!empty($this->uri) && $this->AuthToken && $this->AuthSecret) {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $this->uri);
				curl_setopt($ch, CURLOPT_NOPROGRESS, 1);
				curl_setopt($ch, CURLOPT_VERBOSE, 0);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
				if ($this->method == "PUT" || $this->method == "POST") {
					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $this->buildPayload());
				}
				curl_setopt($ch, CURLOPT_TIMEOUT, 10);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$buffer = curl_exec($ch);
				curl_close($ch);

				if ($buffer !== false) {
					$this->request_return = json_decode($buffer);
				}
			}
		} catch (Exception $ex) {
			//throw our custom excpetion
		}
		return $this->request_return;
	}

	private function buildPayload()
	{
		if ($this->data) {
			$post_data = http_build_query(get_object_vars($this->data));
			return $post_data;
		} else {
			return "";
		}
	}

	private function buildURI(array $creds)
	{
		if ($this->path && $creds['AuthToken'] && $creds['AuthSecret']) {
			$this->AuthToken = $creds['AuthToken'];
			$this->AuthSecret = $creds['AuthSecret'];

			$params = array(
				'api_token'        => $this->AuthToken,
				'api_token_secret' => $this->AuthSecret,
				'_method'          => $this->method,
				'page'             => $this->page,
				'resultsperpage'   => $this->limit
			);
			$uri = $this->baseuri . $this->path . ".json?" . http_build_query($params);

			//add filters if they exist
			if ($this->filter) {
				$uri .= $this->filter->buildRequestQuery();
			}
		}
		return $uri;
	}

	public function getResponse () {
		$response = new ApiResponse();
		if (is_object($this->request_return)) {
			$response->result_ok = $this->request_return->result_ok;
			$response->code = $this->request_return->code;
			$response->message = $this->request_return->message;
			//add meta data
			if (isset($this->request_return->total_count)) {
				$response->total_count = $this->request_return->total_count;
				$response->page = $this->request_return->page;
				$response->total_pages = $this->request_return->total_pages;
				$response->results_per_page = $this->request_return->results_per_page;
			}
			$response->data = $this->request_return->data;
		}
		return $response;
	}

	public function setOptions (array $options = null) {
		// Page #
		if ($options['page'] >= 1) {
			$this->page = $options['page'];
		} else {
			$this->page = 1;
		}

		// Results per page
		if ($options['limit'] >= 1) {
			$this->limit = $options['limit'];
		} else {
			$this->limit = 50;
		}
	}
}
