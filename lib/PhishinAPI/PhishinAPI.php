<?php

class PhishinModel {
	protected static $mapping = [];

	public static function getInstanceFromData(array $arr) {
		$inst = new static($arr);
		return $inst;
	}

	protected $values = [];
	public function __construct(array $arr) {
		$this->values = $arr;
	}
}

class PhishinVenue {
	public function getName() {
		return $this->values['name'];
	}

	public function getId() {
		return $this->values['id'];
	}
}

class SMAPIArgs {
	private $index;
	private $count;
	private $id;
	private $args;

	public function __construct(array $args) {
		$this->index = $args['index'];
		$this->count = $args['count'];
		$this->id = $args['id'];
		$this->args = $args;
	}

	public function index() {
		return $this->index;
	}

	public function count() {
		return $this->count;
	}

	public function id() {
		return $this->id;
	}

	public function otherNamed($name) {
		return $this->args[$name];
	}
}

class PhishinResponse {
	protected $page;
	protected $totalPages;
	protected $totalEntries;

	protected $jsonData;

	public function __construct(array $response) {
		$this->page = $response['page'];
		$this->totalPages = $response['total_pages'];
		$this->totalEntries = $response['total_entries'];
		$this->jsonData = $response['data'];
	}

	public function getPage() {
		return $this->page;
	}

	public function getTotalPages() {
		return $this->totalPages;
	}

	public function getTotalEntries() {
		return $this->totalEntries;
	}

	public function setJsonData(array $data) {
		$this->jsonData = $data;
	}

	public function getSoapArrayResponse($arrayKey, SMAPIArgs $args) {
		return [
			'index'   => $args->index(),
			'total'   => $this->getTotalEntries(),
			'count'   => count($this->jsonData),
			$arrayKey => $this->jsonData
		];
	}
}

class PhishinAPI {
	const PHISHIN_API_BASE = 'http://phish.in/api/v1';

	private static $sharedInstance = null;
	public static function sharedInstance() {
		if(self::$sharedInstance == null) {
			self::$sharedInstance = new PhishinAPI();
		}

		return self::$sharedInstance;
	}

	private function phishinRaw($route, $args = []) {
		$args = array_replace_recursive(['per_page' => 99999], $args);
		$url = 'http://phish.in/api/v1/' . $route . '?' . http_build_query($args);
		return json_decode(file_get_contents($url), true);
	}

	public function years($args = []) {
		return new PhishinResponse($this->phishinRaw('years', $args));
	}

	public function tours($args = []) {
		return new PhishinResponse($this->phishinRaw('tours', $args));
	}

	public function onThisDay($args = []) {
		return new PhishinResponse($this->phishinRaw('shows-on-day-of-year/' . date('m-d'), $args));
	}

	public function venues($args = []) {
		return new PhishinResponse($this->phishinRaw('venues', $args));
	}

	public function songs($args = []) {
		return new PhishinResponse($this->phishinRaw('songs', $args));
	}

	// -----------------------

	public function year($year, $args = []) {
		return new PhishinResponse($this->phishinRaw('years/' . $year, $args));
	}

	public function tour($id, $args = []) {
		return new PhishinResponse($this->phishinRaw('tours/' . $id, $args));
	}

	public function venue($id, $args = []) {
		return new PhishinResponse($this->phishinRaw('venues/' . $id, $args));
	}

	public function show($id, $args = []) {
		return new PhishinResponse($this->phishinRaw('shows/' . $id, $args));
	}

	public function song($id, $args = []) {
		return new PhishinResponse($this->phishinRaw('songs/' . $id, $args));
	}

	public function track($id, $args = []) {
		return new PhishinResponse($this->phishinRaw('tracks/' . $id, $args));
	}

	// -----------------------


}
