<?php

	// PHP-Opencloud Stuff
	use OpenCloud\Rackspace;
	use OpenCloud\Compute\Constants\ServerState;
	use OpenCloud\Compute\Constants\Network;


	class RackspaceAPI {

		// Credentials (Move to make more secure?)
	  	public $username = "userName"; // Replace with username.
	  	public $apiKey   = "APIKEY"; // Replace with API-Key
	  	public $client;
	  	public $service;

	  	public function __construct(){
	  		$this->client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, array(
	      		'username' => $this->username,
	      		'apiKey'   => $this->apiKey
	    	));

	    	// Select RackSpace Service and Location
				//Double check to make sure this is the appropriate location of your server
	    	$this->service = $this->client->computeService('cloudServersOpenStack', 'DFW');
	  	}


		public function checkProgress($serverID){

			// $serverID is passed as a string, but we need it to be an array.
			$ids = explode(",", $serverID);

	  	// Empty array for server IDs to be returned
	  	$buildProgress = array();

	  	foreach($ids as $id){
	  		// Select the RackSpace Serer We Working With
	  		$server = $this->service->server($id);

				// Get the build progress
				$percent = $server->progress;

				// Add progress to array
				array_push($buildProgress, $percent);
			}

			// Send the progress back to the controller
			return $buildProgress;

		} // End checkProgress()


		public function reboot($serverID){
	      // What server are we working with
	      $server = $service->server($serverID);

				// A Soft Reboot (hard reboot = high chance of data loss & corruption)
	      $server->reboot(ServerState::REBOOT_STATE_SOFT);
		} // End reboot()


		public function createServer($flavor_num, $dealerName){
	  	// Ubuntu 12.04 LTS Image (Call imageList() to list all available images)
	  	$ubuntu = $this->service->image('ffa476b1-9b14-46bd-99a8-862d1d94eb7a');

	  	// See link for flavors: http://docs.rackspace.com/servers/api/v2/cs-releasenotes/content/supported_flavors.html
	  	// Get flavor that is passed from the Controller
	  	$flavor = $this->service->flavor($flavor_num);

	  	// Get Server Name that is passed from the Controller:
	  	$name = $dealerName;
	  	$serverNames = array($name."-App", $name."-DB", $name."-Worker");

	  	// Empty array for server IDs to be returned
	  	$serverIDs = array();

	  	// Create Server Stuff
	  	foreach($serverNames as $newServer){
				$server = $this->service->server();
				try {
					$response = $server->create(array(
				  	'name'     => $newServer,
				    'image'    => $ubuntu,
				    'flavor'   => $flavor,
				    'networks' => array(
				    	$this->service->network(Network::RAX_PUBLIC),
				      $this->service->network(Network::RAX_PRIVATE)
				     )
				  ));
				} catch (\Guzzle\Http\Exception\BadResponseException $e) {
					// Something Broke. Check errors and fix.
				  $responseBody = (string) $e->getResponse()->getBody();
				  $statusCode   = $e->getResponse()->getStatusCode();
				  $headers      = $e->getResponse()->getHeaderLines();
				  echo sprintf("Status: %s\nBody: %s\nHeaders: %s", $statusCode, $responseBody, implode(', ', $headers));
				}

				// Need to return Server ID
				$id = explode('"id": "', $response);
				$id = explode('"', $id[1]);

				// Add server ID to array
				array_push($serverIDs, $id[0]);
			} // End foreach

			return $serverIDs;
		} // End createServer()


		public function getServerList(){
		  	$servers = $this->service->serverList();
		  	return $servers;
	  	}


		public function serverInformation($serverID){
			// $serverID is passed as a string, but we need it to be an array.
		  $ids = explode(",", $serverID);

			// Empty array for server IDs to be returned
		  $serverInfo = array();

			foreach($ids as $id){
				// What server are we working with
				$server = $service->server($id);

				  $serverData = array(
			    'serverName'  => $server->name(),
			    'ipAddress'   => $server->ip(),
			  );

				// Set the root password here instead of using random rackspace password
				// Move for security?
				$server->setPassword("newAdmin_Password");

				// Add progress to array
				array_push($serverInfo, $serverData);
			}

			return $serverInfo;

		} // End serverInformation()


		public function imageList(){
			$service = $client->imageService('cloudImages', 'DFW');
  		$images = $service->listImages();

			echo"
  			<table>
    			<tr>
      			<th>Image ID</th>
      			<th>Image Name</th>
    			</tr>
      ";
  		foreach ($images as $image) {
     		echo"
        	<tr>
          	<td>".$image['id']."</td>
            <td>".$image['name']."</td>
           </tr>
        ";
  		}
  		echo "</table>";
		} // End imageList()

	} // End class
