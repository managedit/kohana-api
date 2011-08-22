# API

## Install the OAuth2 Module

   TODO: Detail OAuth2 install etc etc

## Create API Controllers

Something like this:

	<?php defined('SYSPATH') or die('No direct script access.');

	class Controller_API_Jobs extends Controller_API
	{
		/**
		 * GET /api/jobs/:id
		 */
		public function get()
		{
			$id = $this->request->param('id' , FALSE);

			$job = ORM::factory('job', $id);

			/**
			 * Response metadata provides any extra information required. For
			 * example, pagination details like "limit", "offset",
			 * "total_records".
			 */
			$this->_response_metadata += array(
				'sample' => 'data'
			);

			/**
			 * The main API response
			 */
			$this->_response_payload = $job->as_array();

			/**
			 * Response links indicate where you can go next, typically this
			 * includes actions that can be performed on the current
			 * resources, as well as links to related resources
			 */
			$this->_response_links += array(
				'create' => $this->_generate_link('POST',   '/api/jobs'),
				'read'   => $this->_generate_link('GET',    '/api/jobs/:id', array(
					':id' => 'id',
				)),
				'update' => $this->_generate_link('PUT',    '/api/jobs/:id', array(
					':id' => 'id',
				)),
				'delete' => $this->_generate_link('DELETE', '/api/jobs/:id', array(
					':id' => 'id',
				)),
				'owner'   => $this->_generate_link('GET',   '/api/user/:id', array(
					':id' => 'id',
				)),
			);
		}

		/**
		 * GET /api/jobs/:id/custom
		 */
		public function get_custom()
		{
			/**
			 * Custom actions can be used for any purpose (Unless you insist on
			 * 100% RESTful!).
			 *
			 * One example of usage might be nesting resources - eg:
			 *
			 * * GET /jobs/1/owner - Get the owner of this job.
			 * * GET /jobs/1/parts - Get the collection of parts required for this job.
			 *
			 * They take the format:
			 *
			 *    public function :method_:custom() {}
			 *
			 * or
			 *
			 *    public function :method_:custom_collection() {}
			 */
		}

		/**
		 * GET /api/jobs
		 */
		public function get_collection()
		{
			$jobs = ORM::factory('job')->find_all();

			$this->_response_metadata += array(
				'test' => 'test'
			);

			$this->_response_payload = $jobs->map(function($val) {
				return $val->as_array();
			});
		}

		/**
		 * Create a new job
		 *
		 * POST /api/jobs
		 */
		public function post_collection()
		{
			$job = ORM::factory('job');

			$job->values($this->_request_payload, array(
				'name',
			))->save();

			$this->_response_payload = $job->as_array();
		}

		/**
		 * Update a job
		 *
		 * PUT /api/jobs/:id
		 */
		public function put()
		{
			$id = $this->request->param('id' , FALSE);

			$job = ORM::factory('job', $id);

			$job->values($this->_request_payload, array(
				'name',
			))->save();

			$this->_response_payload = $job->as_array();
		}

		/**
		 * Delete a job
		 *
		 * DELETE /api/jobs/:id
		 */
		public function delete()
		{
			$id = $this->request->param('id' , FALSE);

			$job = ORM::factory('job', $id);

			$job->values($this->_request_payload, array(
				'name',
			))->save();
		}

		/**
		 * Delete all jobs
		 *
		 * DELETE /api/jobs
		 */
		public function delete_collection()
		{
			$jobs = ORM::factory('job')->find_all();

			foreach ($jobs as $job)
			{
				$job->delete();
			}
		}
	}

## Example resource output:

	{
		"metadata": {
			"error": false,
			"sample": "data"
		},
		"links": {
			"create": {
				"method": "POST",
				"url": "\/api\/jobs",
				"parameters": []
			},
			"read": {
				"method": "GET",
				"url": "\/api\/jobs\/:id",
				"parameters": {
					":id": "id"
				}
			},
			"update": {
				"method": "PUT",
				"url": "\/api\/jobs\/:id",
				"parameters": {
					":id": "id"
				}
			},
			"delete": {
				"method": "DELETE",
				"url": "\/api\/jobs\/:id",
				"parameters": {
					":id": "id"
				}
			},
			"owner": {
				"method": "GET",
				"url": "\/api\/user\/:id",
				"parameters": {
					":id": "id"
				}
			}
		},
		"payload": {
			"id": "3",
			"company_id": "2",
			"name": "Job 2",
			"created": "1314043166",
			"updated": null
		}
	}

## Example collection output:

	{
		"metadata": {
			"error": false,
			"test": "test"
		},
		"links": [],
		"payload": [{
			"id": "2",
			"company_id": "1",
			"name": "Job 1",
			"created": "1314043166",
			"updated": null
		}, {
			"id": "3",
			"company_id": "2",
			"name": "Job 2",
			"created": "1314043166",
			"updated": null
		}]
	}