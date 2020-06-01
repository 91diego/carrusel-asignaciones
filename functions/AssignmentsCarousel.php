<?php

	/**
	* Assign leads to manager or sales advisor
	*
	* @param int $leadId
	* @param string $projectName
	* @param string $type
	*
	* @return bool
	*/
	function assignments($leadId, $projectName, $type) {

		/* 
			VERIFY THE TYPE OF THE ASSIGNMENT 
			IT COULD BE MANAGER OR SALES ADVISOR
		*/
		switch ($type) {

			case 'manager':

				$userUrl = "https://intranet.idex.cc/rest/117/w0qdwl5fbr0hpuf1/user.get.json?FILTER[WORK_POSITION]=GERENTE%20DE%20VENTAS&FILTER[ACTIVE]=true&FILTER[PERSONAL_STATE]=0";
				$userJson = file_get_contents($userUrl);
  				$user = json_decode($userJson, true);
  				$numberOfUsers = count($user["result"]);

  				// GUARDAMOS EN EL ARREGLO LOS DATOS IMPORTANTES
  				for ($i = 0; $i < $numberOfUsers; $i++) {

  					responsableToLead($user["result"][$i]["ID"], $leadId);
  					changePersonalState($user["result"][$i]["ID"]);
				    break;
  				}

  				// SI LA CONDICION SE CUMPLE SE REINICIA EL CARRUSEL DE ASIGNACIONES
  				if ($numberOfUsers < 1) {

  					resetCarousel($user["result"]);
  				}

				break;

			case 'sales-advisor':
				echo "Asignacion de leads a los asesores";
				echo "Se realiza una busqueda en los usuarios en donde su cargo sea asesor de ventas";
				break;
			
			default:
				// code...
				break;
		}
	}

	/**
	* Assign responsable to lead
	* @param int $userId
	*
	* @return bool
	*/
	function changePersonalState($userId) {

		$updatePersonalState = "https://intranet.idex.cc/rest/117/w0qdwl5fbr0hpuf1/user.update.json?ID=".$userId."&PERSONAL_STATE=1";
	    $curl = curl_init($updatePersonalState);
	    // Configuring curl options
	    $options = array(

	        CURLOPT_RETURNTRANSFER => true,
	        CURLOPT_HTTPHEADER => array('Accept: application/json'),
	        CURLOPT_SSL_VERIFYPEER => false,
	    );
	    // Setting curl options
	    curl_setopt_array($curl, $options);
	    // Getting results
	    $response = curl_exec($curl);
	    // Cerrar el recurso cURL y liberar recursos del sistema
	    curl_close($curl);
	    $data = json_decode($response, true);
	}

	/**
	* Assign responsable to lead
	* @param int $userId
	*
	* @return bool
	*/
	function responsableToLead($userId, $leadId) {

	    // URL QUE CONTIENE LE METODO DE LA API PARA LAS ASIGNACIONES DEL CARRUSEL
	    $assignLeadResponsable = 'https://intranet.idex.cc/rest/117/w0qdwl5fbr0hpuf1/crm.lead.update.json';
	    $queryData = http_build_query(
	      array(
	        'id' => $leadId,
	        'fields' => array(
	        "ASSIGNED_BY_ID" => $userId
	        ),
	        'params' => array("REGISTER_SONET_EVENT" => "Y")
	      )
	    );

	    $curl = curl_init();
	    curl_setopt_array($curl, array(
	    CURLOPT_SSL_VERIFYPEER => 0,
	    CURLOPT_POST => 1,
	    CURLOPT_HEADER => 0,
	    CURLOPT_RETURNTRANSFER => 1,
	    CURLOPT_URL => $assignLeadResponsable,
	    CURLOPT_POSTFIELDS => $queryData,
	    ));

	    $result = curl_exec($curl);
	    curl_close($curl);
	    $result = json_decode($result, 1);

			echo "<p> La negociacion: ".$leadId.", ha sido asignada a el ID de gerente ".$userId." </p>";
	}

	/**
	* Reset assignments carousel
	* @param array $data
	*
	* @return bool
	*/
	function resetCarousel($data) {

		$userUrl = "https://intranet.idex.cc/rest/117/w0qdwl5fbr0hpuf1/user.get.json?FILTER[WORK_POSITION]=GERENTE%20DE%20VENTAS&FILTER[ACTIVE]=true&FILTER[PERSONAL_STATE]=1";
		$userJson = file_get_contents($userUrl);
		$user = json_decode($userJson, true);
		$numberOfUsers = count($user["result"]);

		for ($i = 0; $i < $numberOfUsers; $i++) {
			$updatePersonalState = "https://intranet.idex.cc/rest/117/w0qdwl5fbr0hpuf1/user.update.json?ID=".$user["result"][$i]["ID"]."&PERSONAL_STATE=0";
		    $ch = curl_init($updatePersonalState);
		    // Configuring curl options
		    $options = array(

		        CURLOPT_RETURNTRANSFER => true,
		        CURLOPT_HTTPHEADER => array('Accept: application/json'),
		        CURLOPT_SSL_VERIFYPEER => false,
		    );
		    // Setting curl options
		    curl_setopt_array($ch, $options);
		    // Getting results
		    $response = curl_exec($ch);
		    // Cerrar el recurso cURL y liberar recursos del sistema
		    curl_close($ch);
		    $data = json_decode($response, true);
		}

		echo "<p>El carrusel de asignaciones ha sido reiniciado</p>";
	}

?>