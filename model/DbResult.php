<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA;
 *
 *
 */
namespace oat\taoOutcomeRdf\model;

use \common_Logger;
use \common_ext_ExtensionsManager;
use \core_kernel_classes_Class;
use \core_kernel_classes_Property;
use \core_kernel_classes_Resource;
use oat\taoOutcomeRdf\model\ResultsService;
use oat\taoResultServer\models\classes\ResultManagement;
use \taoResultServer_models_classes_Variable;
use \taoResultServer_models_classes_WritableResultStorage;
use \tao_models_classes_GenerisService;

class DbResult
    extends tao_models_classes_GenerisService
    implements
    taoResultServer_models_classes_WritableResultStorage,
    ResultManagement
{

    /**
     *
     * @var ResultsService
     */
    private $taoResultsStorage;


    /**
     * @param string deliveryResultIdentifier if no such deliveryResult with this identifier exists a new one gets created
     */

    public function __construct()
    {
        parent::__construct();
        common_ext_ExtensionsManager::singleton()->getExtensionById("taoOutcomeRdf");
        $this->taoResultsStorage = ResultsService::singleton();

    }

    /**
     * In the case of a taoResultsDB storage and if the consumer asks for an identifer a uri is returned
     * * //you may also provide your own identifier to the other services like a lis_result_sourcedid:GUID
     */
    public function spawnResult($deliveryResultIdentifier = null)
    {

        $spawnedResult = $this->taoResultsStorage->storeDeliveryResult($deliveryResultIdentifier)->getUri();
        common_Logger::i("taoOutcomeRdf storage spawned result:" . $spawnedResult);
        return $spawnedResult;
    }

    /**
     * @param string testTakerIdentifier (uri recommended)
     *
     */
    public function storeRelatedTestTaker($deliveryResultIdentifier, $testTakerIdentifier)
    {
        // spawns a new delivery result or retrieve an existing one with this identifier
        $deliveryResult = $this->taoResultsStorage->storeDeliveryResult($deliveryResultIdentifier);
        $this->taoResultsStorage->storeTestTaker($deliveryResult, $testTakerIdentifier);
    }

    /**
     * @param string deliveryIdentifier (uri recommended)
     */
    public function storeRelatedDelivery($deliveryResultIdentifier, $deliveryIdentifier)
    {
        //spawns a new delivery result or retrieve an existing one with this identifier
        $deliveryResult = $this->taoResultsStorage->storeDeliveryResult($deliveryResultIdentifier);
        $this->taoResultsStorage->storeDelivery($deliveryResult, $deliveryIdentifier);
    }

    /**
     * Submit a specific Item Variable, (ResponseVariable and OutcomeVariable shall be used respectively for collected data and score/interpretation computation)
     * @param string test (uri recommended)
     * @param string item (uri recommended)
     * @param taoResultServer_models_classes_ItemVariable itemVariable
     * @param string callId contextual call id for the variable, ex. :  to distinguish the same variable output by the same item but taht is presented several times in the same test
     */
    public function storeItemVariable(
        $deliveryResultIdentifier,
        $test,
        $item,
        taoResultServer_models_classes_Variable $itemVariable,
        $callIdItem
    ) {
        //spawns a new delivery result or retrieve an existing one with this identifier
        $deliveryResult = $this->taoResultsStorage->storeDeliveryResult($deliveryResultIdentifier);
        $this->taoResultsStorage->storeItemVariable($deliveryResult, $test, $item, $itemVariable, $callIdItem);

    }

    /** Submit a complete Item result
     *
     * @param taoResultServer_models_classes_ItemResult itemResult
     * @param string callId an id for the item instanciation
     */
    public function storeTestVariable(
        $deliveryResultIdentifier,
        $test,
        taoResultServer_models_classes_Variable $testVariable,
        $callIdTest
    ) {
        $deliveryResult = $this->taoResultsStorage->storeDeliveryResult($deliveryResultIdentifier);
        $this->taoResultsStorage->storeTestVariable($deliveryResult, $test, $testVariable, $callIdTest);

    }

    public function configure(core_kernel_classes_Resource $resultServer, $callOptions = array())
    {
        //nothing to configure in the case of taoResults storage
    }

    public function deleteResult($deliveryResultIdentifier)
    {
        return $this->taoResultsStorage->deleteResult(
            new core_kernel_classes_Resource($deliveryResultIdentifier)
        );
    }

    public function getDelivery($deliveryResultIdentifier)
    {
        return current(
            $this->getDeliveryResult($deliveryResultIdentifier)
                 ->getPropertyValues(new core_kernel_classes_Property(PROPERTY_RESULT_OF_DELIVERY))
        );
    }

    /**
     * 
     * @param array $columns
     * @param array $filter
     * @return array
     * @throws common_exception_Error
     */
    private function getFilters($columns, $filter)
    {
        $searchTarget = array(
            'deliveryResultIdentifier' => PROPERTY_IDENTIFIER,
            'testTakerIdentifier' => PROPERTY_RESULT_OF_SUBJECT,
            'deliveryIdentifier' => PROPERTY_RESULT_OF_DELIVERY
        );

        $search = array();
        foreach ($searchTarget as $key => $target) {
            if (isset($filter[$key])) {
                $search[$target] = $filter[$key];
                break;
            }
        }

        // make sure we have an identifier to search for
        if (empty($search)) {
            throw new \common_exception_Error('Search is missing an identifier');
        }

        foreach ($columns as $column) {

            if (isset($filter[$column])) {
                $search[$column] = $filter[$column];
            }
        }

        return $search;

    }

    /**
     * @see oat\taoResultServer\models\classes\ResultManagement::countResultByFilter
     */
    public function countResultByFilter($columns, $filter) {

        $propertyFilters = $this->getFilters($columns, $filter);

        $deliveryResultClass = new core_kernel_classes_Class(TAO_DELIVERY_RESULT);

        $returnValue = (int) 0;

        $returnValue = $deliveryResultClass->countInstances(
            $propertyFilters,
            array(
                'like'      => false,
                'recursive' => false
            )
        );

        return $returnValue;
    }

    public function getResultByColumn($columns, $filter, $options = array())
    {

        $returnValue = array();
        $options['like'] = false;
        $propertyFilters = $this->getFilters($columns, $filter);
        $deliveryResultClass = new core_kernel_classes_Class(TAO_DELIVERY_RESULT);

        $deliveryResults = $deliveryResultClass->searchInstances($propertyFilters, $options);

        if (!is_array($deliveryResults)) {
            return $returnValue;
        }

        $properties = array(
            PROPERTY_IDENTIFIER,
            PROPERTY_RESULT_OF_SUBJECT,
            PROPERTY_RESULT_OF_DELIVERY
        );

        foreach ($deliveryResults as $deliveryResult) {
            $values = $deliveryResult->getPropertiesValues($properties);

            $ret = array();
            foreach ($values as $key => $property) {
                $ret[$key] = $property[0]->getUri();
            }
            $returnValue[] = $ret;

        }

        return $returnValue;
    }

    public function getTestTaker($deliveryResultIdentifier)
    {
        return current(
            $this->getDeliveryResult($deliveryResultIdentifier)
                 ->getPropertyValues(new core_kernel_classes_Property(PROPERTY_RESULT_OF_SUBJECT))
        );
    }

    public function getAllCallIds()
    {
        $deliveryResultClass = new core_kernel_classes_Class(TAO_DELIVERY_RESULT);
        $deliveryResults = $deliveryResultClass->searchInstances();

        $returnValue = array();

        foreach($deliveryResults as $deliveryResult) {
            $returnValue[] = $deliveryResult->getUniquePropertyValue(new core_kernel_classes_Property(RDFS_LABEL))->__toString();
        }

        return $returnValue;
    }

    public function getAllDeliveryIds()
    {
        $deliveryResultClass = new core_kernel_classes_Class(TAO_DELIVERY_RESULT);
        $deliveryResults = $deliveryResultClass->searchInstances();

        $returnValue = array();

        foreach ($deliveryResults as $deliveryResult) {
            $properties = $deliveryResult->getPropertiesValues(array(
                PROPERTY_IDENTIFIER,
				PROPERTY_RESULT_OF_SUBJECT,
                PROPERTY_RESULT_OF_DELIVERY
            ));

            $returnValue[] = array(
				'deliveryResultIdentifier' => $properties[PROPERTY_IDENTIFIER][0]->getUri(),
				'testTakerIdentifier'      => $properties[PROPERTY_RESULT_OF_SUBJECT][0]->getUri(),
                'deliveryIdentifier'       => $properties[PROPERTY_RESULT_OF_DELIVERY][0]->getUri(),
            );
        }

        return $returnValue;
    }

    public function getAllTestTakerIds()
    {
        $deliveryResultClass = new core_kernel_classes_Class(TAO_DELIVERY_RESULT);
        $deliveryResults = $deliveryResultClass->searchInstances();

        $returnValue = array();

		foreach ($deliveryResults as $deliveryResult) {
			$properties = $deliveryResult->getPropertiesValues(array(
				PROPERTY_IDENTIFIER,
				PROPERTY_RESULT_OF_SUBJECT,
                PROPERTY_RESULT_OF_DELIVERY
			));

			$returnValue[] = array(
				'deliveryResultIdentifier' => $properties[PROPERTY_IDENTIFIER][0]->getUri(),
				'testTakerIdentifier'      => $properties[PROPERTY_RESULT_OF_SUBJECT][0]->getUri(),
				'deliveryIdentifier'       => $properties[PROPERTY_RESULT_OF_DELIVERY][0]->getUri()
			);
		}

        return $returnValue;
    }

    public function getRelatedItemCallIds($deliveryResultIdentifier)
    {
        $data = $this->taoResultsStorage->getItemResultsFromDeliveryResult(
			new core_kernel_classes_Resource($deliveryResultIdentifier)
		);
        return $data;
    }

    public function getVariable($callId, $variableIdentifier)
    {
		return $this->taoResultsStorage->getVariableData(
			new core_kernel_classes_Resource($variableIdentifier)
		);
    }

    public function getVariableProperty($variableId, $property)
    {
        $variable = new core_kernel_classes_Resource($variableId);
        $returnValue = $variable->getOnePropertyValue($property);

        return $returnValue;
    }

    public function getVariables($callId)
    {
		return $this->taoResultsStorage->getVariablesFromItemResult(
			new core_kernel_classes_Resource($callId)
		);
    }

	/**
	 * get delivery result from its Identifier
	 * @param string $deliveryResultIdentifier
	 * @return core_kernel_classes_Resource
	 * @throws common_exception_Error
	 */
    public function getDeliveryResult($deliveryResultIdentifier)
    {
        $deliveryResultClass = new core_kernel_classes_Class(TAO_DELIVERY_RESULT);
        $deliveryResults = $deliveryResultClass->searchInstances(
            array(PROPERTY_IDENTIFIER => $deliveryResultIdentifier),
            array('like' => false, 'recursive' => false)
        );

        if (count($deliveryResults) == 1) {
            return current($deliveryResults);
        }

        if (empty($deliveryResults)) {
            throw new common_exception_Error('Delivery result for the corresponding Id ' . $deliveryResultIdentifier . ' could not be found');
        }
        else {
            throw new common_exception_Error('More than 1 delivery result for the corresponding Id ' . $deliveryResultIdentifier);
        }
		
    }

}