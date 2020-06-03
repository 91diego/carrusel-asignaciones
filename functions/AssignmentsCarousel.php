<?php

	/**
	* Assign leads to manager or sales advisor
	*
	* @param int $leadId
	* @param string $projectName
	* @param string $type
	* @param string $responsable -> ID of responsable if lead exists
	*
	* @return bool
	*/
	function assignments($leadId, $projectName, $type, $data) {

		/* 
			VERIFY THE TYPE OF THE ASSIGNMENT
			IT COULD BE MANAGER OR SALES ADVISOR
		*/
		switch ($type) {

			case 'manager':

				// IF THE LEAD EXIST, WE ASSIGN THE RECORD TO LE LAST RESPONSABLE
				// AND THE PROCESS END
				if ($data == 0) {

					$userUrl = "https://intranet.idex.cc/rest/117/w0qdwl5fbr0hpuf1/user.get.json?FILTER[WORK_POSITION]=GERENTE%20DE%20VENTAS&FILTER[ACTIVE]=true&FILTER[PERSONAL_STATE]=0";
					$userJson = file_get_contents($userUrl);
	  				$user = json_decode($userJson, true);
	  				$numberOfUsers = count($user["result"]);

	  				// SI LA CONDICION SE CUMPLE SE REINICIA EL CARRUSEL DE ASIGNACIONES
	  				// DE LO CONTRARIO SE REALIZA LA ASIGNACION AL SIGUIENTE USUARIO
	  				if ($numberOfUsers < 1) {

	  					$userData = resetCarousel($user["result"]);
	  					$number = count($userData);
			  			for ($i = 0; $i < $number; $i++) {

		  					responsableToLead($userData[$i]["ID"], $leadId, "");
		  					changePersonalState($userData[$i]["ID"]);
						    break;
		  				}
	  				} else {

		  				for ($i = 0; $i < $numberOfUsers; $i++) {

		  					responsableToLead($user["result"][$i]["ID"], $leadId, "");
		  					changePersonalState($user["result"][$i]["ID"]);
						    break;
		  				}
	  				}

				} elseif ($data["ASSIGNED_BY_ID"] > 0) {

					$comments = "El prospecto habia sido asignado anteriormente a ". $data["ASSIGNED_NAME"].", que tiene el ID ". $data["ASSIGNED_BY_ID"]." en el CRM. Pertenece al departamento ".$data["DEPARTMENT_NAME"]." y su gerente responsable es ".$data["RESPONSABLE_DEPARTMENT"];
					responsableToLead($data["ASSIGNED_BY_ID"], $leadId, $comments);
				}
				break;

			case 'sales-advisor-general':
				echo "Asignacion de leads a los asesores";
				echo "Se realiza una busqueda en los usuarios en donde su cargo sea asesor de ventas. LAS ASIGNACIONES SON GENERALES SIN IMPORTAR EL DESARROLLO";
				break;

			case 'sales-advisor-project':
				echo "Asignacion de leads a los asesores";
				echo "Se realiza una busqueda en los usuarios en donde su cargo sea asesor de ventas. LAS ASIGNACIONES SON POR DESARROLLO";
				break;

			case 'top-sales-advisor':
				echo "Asignacion de leads a los asesores";
				echo "Se realiza una busqueda en los usuarios en donde su cargo sea asesor de ventas. LAS ASIGNACIONES SE REALIZAN MEDIANTE UNA CLASIFICACION";
				break;
			
			default:
				// code...
				break;
		}
	}


	/**
	* Prevents duplicate records
	* @param int $leadId
	*
	* @return bool
	*/
	function searchDuplicateRecord($leadId) {

		// SAVE THE SPECIFIC DATA OF THE LEAD
		$leadData = [];
		// OBTAIN DATA OF THE LEAD
		$leadUrl = "https://intranet.idex.cc/rest/117/w0qdwl5fbr0hpuf1/crm.lead.get?ID=".$leadId;
		$leadJson = file_get_contents($leadUrl);
  		$leadResponse = json_decode($leadJson, true);
  		array_push($leadData, [
  			"name" => $leadResponse["result"]["NAME"],
  			"last_name"=> $leadResponse["result"]["LAST_NAME"],
  			"phone"=> $leadResponse["result"]["PHONE"][0]["VALUE"],
  			"email"=> $leadResponse["result"]["EMAIL"][0]["VALUE"],
  		]);

  		$name = $leadData[0]["name"];
  		$lastName = $leadData[0]["last_name"];
  		$phone = $leadData[0]["phone"];
  		$email = $leadData[0]["email"];

		$findDuplicatesUrl = "https://intranet.idex.cc/rest/117/w0qdwl5fbr0hpuf1/crm.lead.list?";
        $options = array("FILTER[NAME]"=>$name, "FILTER[LAST_NAME]"=>$lastName, "FILTER[PHONE]"=>$phone, "FILTER[EMAIL]"=>$email, "sensor"=>"false");
	    $findDuplicatesUrl .= http_build_query($options,'','&');
	    $duplicatesJson = file_get_contents($findDuplicatesUrl) or die(print_r(error_get_last()));
	    $duplicatesResponse = json_decode($duplicatesJson, true);

  		if (!empty($duplicatesResponse)) {

  			// WE HAVE TO VALIDATE THE ID`S OF THE RECORDS
  			// IF THE ID`S ARE EQUALS, MEANS THAT IS THE SEMA RECORD
  			// IF THE ID`S ARE DIFFERENT, WE HAVE TO ASSIGN THE NEW RECORD
  			// TO THE MANAGER OF THE SALES ADVISOR AND PUT THE RECORD IN A NEW STATMENT
  			$elementsNumber = count($duplicatesResponse["result"]);
  			$duplicateData = [];
  			for ($i = 0; $i < $elementsNumber; $i++) {

	  			if ($leadId != $duplicatesResponse["result"][$i]["ID"]) {
	  				
	  				// echo "Los datos del prospecto con el ID $leadId ya fueron registrado en el ID ".$duplicatesResponse["result"][$i]["ID"].".<br>";
	  				$userResult = searchUserName($duplicatesResponse["result"][$i]["ASSIGNED_BY_ID"]);
	  				$department = departments($userResult[0]["UF_DEPARTMENT"][1]);
	  				$managersName = searchUserName($department[0]["UF_HEAD"]);
	  				// INSERT DATA OF THE OLD (OR FIRST RECORD IN CRM) RECORD
	  				array_push($duplicateData, [

	  					"ID" => $leadId,
	  					"ASSIGNED_BY_ID" => $duplicatesResponse["result"][$i]["ASSIGNED_BY_ID"],
	  					"ASSIGNED_NAME" => $userResult[0]["NAME"]." ".$userResult[0]["LAST_NAME"],
	  					"DEPARTMENT_ID" => $department[0]["ID"],
	  					"DEPARTMENT_NAME" => $department[0]["NAME"],
	  					"ID_HEAD" => $department[0]["UF_HEAD"],
	  					"RESPONSABLE_DEPARTMENT" => $managersName[0]["NAME"]." ".$managersName[0]["LAST_NAME"]
	  				]);
	  				return $duplicateData;
	  			}
  			}
  			return $duplicateData;
  		}
	}

	/**
	* Obtain the data of the department
	* @param int $departmentId
	*
	* @return bool
	*/
	function departments($departmentId) {

		// INFORMACION RESPONSABLE
        $detailsDepartment = "https://intranet.idex.cc/rest/117/w0qdwl5fbr0hpuf1/department.get?ID=".$departmentId;
        // OBTIENE LA RESPUESTA DE LA API REST BITRIX
        $responseAPI = file_get_contents($detailsDepartment);

        // CAMPOS DE LA RESPUESTA
        $department = json_decode($responseAPI, true);
        // FIN INFORMACION RESPOSABLE
        return $department["result"];		
	}

	/**
	* Search the user and obtain the data
	* @param int $userId
	*
	* @return bool
	*/
	function searchUserName($userId) {

		// INFORMACION RESPONSABLE
        $detailsResponsable = "https://intranet.idex.cc/rest/117/w0qdwl5fbr0hpuf1/user.get?ID=".$userId;
        // OBTIENE LA RESPUESTA DE LA API REST BITRIX
        $responseAPI = file_get_contents($detailsResponsable);

        // CAMPOS DE LA RESPUESTA
        $responsable = json_decode($responseAPI, true);
        // FIN INFORMACION RESPOSABLE
        return $responsable["result"];
	}

	/**
	* Assign responsable to lead
	* @param int $userId
	*
	* @return bool
	*/
	function changePersonalState($userId/*, $numberUsers, $data*/) {

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
	* @param int $leadId
	* @param string comments -> ONLY IF THE RECORD EXIST
	* @return bool
	*/
	function responsableToLead($userId, $leadId, $comments) {

	    // URL QUE CONTIENE LE METODO DE LA API PARA LAS ASIGNACIONES DEL CARRUSEL
	    $assignLeadResponsable = 'https://intranet.idex.cc/rest/117/w0qdwl5fbr0hpuf1/crm.lead.update.json';
	    $queryData = http_build_query(
	      array(
	        'id' => $leadId,
	        'fields' => array(
	        "ASSIGNED_BY_ID" => $userId,
	        "COMMENTS" => $comments
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

		$updatePersonalState = "https://intranet.idex.cc/rest/117/w0qdwl5fbr0hpuf1/user.get.json?FILTER[WORK_POSITION]=GERENTE%20DE%20VENTAS&FILTER[ACTIVE]=true&FILTER[PERSONAL_STATE]=0";
		$userUpdate = file_get_contents($updatePersonalState);
		$user = json_decode($userUpdate, true);
		$userData = $user["result"];
		echo "<p>El carrusel de asignaciones ha sido reiniciado</p>";
		return $userData;
	}

?>