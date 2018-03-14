<?php namespace arcadia;

	/**
	 * ApiToPostman class
	 * 
	 *  7-feb-2018	new
	 *  
	 *  How To Use
	 *  
	 *  require_once "<path>/ApiToPostman.class.php"
	 *  
	 *  $atp = new \arcadia\ApiToPostman();
	 *  	  
	 *  // you can run either or both of the following functions
	 *  
	 *  // get the JSON for POSTMAN
	 *  $json = $atp->getPostman();
	 *  
	 *  // save the JSON into a file
	 *  $atp->outputPostman('/tmp/postman.json');
	 *  
	 *  @author Greg Ambrose  greg@ambrose.id.au  http://greg.ambrose.id.au
	 */

class ApiToPostman
{
	protected $request;		// request held here in array
	protected $json;		// json for this request
	protected $domain;		// incase we want to change it
	
	// these headers get ignored
	protected $headersToIgnore = [
		'Postman-Token',
		'content-length',
		'Host',
		'cache-control',
		'Content-Type',
		'User-Agent',
		'accept-encoding'.
		'Connection'
	];
	
	
	public function __construct($domain = NULL)
	{
		// on some servers and under CLI this function doesnt exist. We could add a php replacement but
		// for now we will just generate an error
		if (!function_exists('getallheaders')) trigger_error("No getallheaders function", E_USER_ERROR);
		
		$this->domain = $domain;
		$this->processRequest();
	}
	
	/**
	 * Take the request details and return as JSON
	 *
	 * @param  void
	 * @return string  (the JSON)
	 */
	
	public function getPostman()
	{
		return $this->json;
	}
	
	/**
	 * Take the request details and ave them for later use
	 *
	 * @param file name for to write JSON to
	 * @param do we create new file or add to existing (create or add)?
	 * @return void
	 */
	
	public function outputPostman($fileName, $createOrAdd = 'create')
	{
		// if file not there, it has to be a create
		if(!file_exists($fileName)) $createOrAdd = 'create';
		
		if($createOrAdd == 'create')
		{
			// We define the scruture of the collection
			$contents = array();
			
			$contents['info'] =
				[	'name' => 'Auto Get Collection - ' . date('d-m-Y H:i'),
					'_postman_id' => uniqid(),
					'description' => '',
					'schema' => "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
				];
	
			$contents['item'] = array();
		}
		else 
		{
			// --- file exists, must be JSON -----
			$c = file_get_contents($fileName);
			if($c === false) trigger_error("ApiToPostman: cant get contents of $fileName", E_USER_ERROR);
			
			$contents = json_decode($c, true);
			if($contents == false) trigger_error("ApiToPostman: contents of $fileName not proper JSON", E_USER_ERROR);
		}
		
		// --- now add array for current request
		$item = array();
		
		$item['name'] = 'Auto Test Script - ' . time();
		
		// array of actual request
		$request = $this->request;  
		
		// array we put in putput for POSTMAN
		$r = array();
		$r['method'] 		= $request['method'];
		$r['header'] 		= array();
		$r['body'] 			= array();
		$r['url'] 			= array();
		$r['description'] 	= 'Auto generated';
		
		
		/// ---- URL fields ------
		
		// we need the host name broken up into bits by full stop
		$host = explode('.', $request['host']);
		$p    = explode('/', $request['uri']);
		
		// but will have empty element at start
		$path = array();
		foreach($p as $key => $value)
		{
			if($value == '') continue;
			
			$path[] = $value;
		}
			
		
		$url = [
			"raw" => $request['url'],
			"protocol" =>$request['protocol'],
			"host" => $host,
			"path" => $path
		];
		$r['url'] = $url;
		
		// ----- Header ------
		$h = array();
		
		// we may need to filter some out
		foreach($request['headers'] as $key => $value)
		{
			// this may be a type of header we wish to ignore
			if(in_array($key, $this->headersToIgnore)) continue;
	
			// keep these
			$h[] = [ 'key' => $key, 'value' => $value];
		}
		
		$r['header'] = $h;
		
		// ---------- Form Data - with form fields --------------
		$b = array();
		
		// merge get and post
		$x = $request['get'] +  $request['post'];
		
		// we may need to filter some out
		$f = array();
		foreach($x as $key => $value)
		{
			$f[] = ['key' => $key, 'value' => $value, 'type' => 'text'];
		}
		
		if(count($f) > 0)
		{
			$b['mode'] = "formdata";
			$b['formdata'] = $f;
		}
		
		$r['body'] = $b;
		
		// ------- When actual data replace form data ! --------------
		$d = '';
		if(strlen($request['data']) > 0)
		{
			$d = [ "mode" => "raw",
				"raw" => $request['data']
				];
		}
		
		$r['body'] = $d;
		
		// --- now save the whole request
		$item['request'] = $r;
		
		$contents['item'][] = $item;
			
		$this->json = json_encode($contents);
		
		$fp = fopen($fileName,'w');
		if($fp == false) trigger_error("Cant open for $fileName for output", E_USER_ERROR);
		
		fwrite($fp,$this->json);
		fclose($fp);
	}
	
	// ------------------------- Private Functions ------------------------
	
	/**
	 * Take the request details and save them for later use
	 *
	 * @param void
	 * @return void
	 */
	
	protected function processRequest()
	{
		// handle put and patch through this input
		$data = file_get_contents('php://input');
		
		$host 		= isset($_SERVER['HTTP_HOST']) 		? $_SERVER['HTTP_HOST'] : '';
		$protocol 	= isset($_SERVER['REQUEST_SCHEME']) 	? $_SERVER['REQUEST_SCHEME'] : '';  // like http
		$uri 		= isset($_SERVER['REQUEST_URI']) 	? $_SERVER['REQUEST_URI'] : '';
		$url 		= "{$protocol}://{$host}{$uri}";
		$method 	= isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
		$get 		= isset($_GET) 						? $_GET: 	array();
		$post 		= isset($_POST) 					? $_POST : 	array();
		
		$headers 	= getallheaders();
		
		// if we defined a domain, we need to change a few things first
		if($this->domain != NULL)
		{
			$domain = $this->domain;
			
			$host = $domain;
		}
		
		$url 		= "{$protocol}://{$host}{$uri}";
		
		$out = [
			'url' => 		$url,
			'host' => 		$host,
			'protocol' => 	$protocol,
			'uri' => 		$uri,
			'method' => 	$method,
			'headers' => 	$headers,
			'get' => 		$get,
			'post' => 		$post,
			'data' => 		$data,
		];
		
		$this->request = $out;
	}
}

?>