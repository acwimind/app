<?php

/**
 * Depending on request type (web or API) pick a suitable controller
 * Inherit its functionality into AppController to make it available for whole app
 */

if (substr($_SERVER['REQUEST_URI'], 0, 5) == '/api/') {	//app controller for API requests

	App::uses('AppApiController', 'Controller');
    App::uses('RequestHandler','Controller/Component');

	class AppController extends AppApiController {
	}

} else {	//app controller for web requests

	App::uses('AppWebController', 'Controller');
	App::uses('RequestHandler','Controller/Component');
    class AppController extends AppWebController {
	}

}