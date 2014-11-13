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
 * Copyright (c) 2013 Open Assessment Technologies S.A.
 * 
 *
 * @access public
 * @author Joel Bout, <joel.bout@tudor.lu>
 * @package taoResults
 * 
 */
namespace oat\taoOutcomeRdf\model;

use \Exception;
use \common_Exception;
use \common_Logger;
use \common_cache_FileCache;
use \common_exception_Error;
use \core_kernel_classes_Class;
use \core_kernel_classes_Property;
use \core_kernel_classes_Resource;
use \taoResultServer_models_classes_Variable;
use \taoResultServer_models_classes_ResponseVariable;
use \taoResultServer_models_classes_OutcomeVariable;
use \taoResultServer_models_classes_TraceVariable;
use \tao_helpers_Date;
use \tao_models_classes_ClassService;
use qtism\common\datatypes\Float;
use qtism\common\enums\BaseType;
use qtism\common\enums\Cardinality;
use qtism\runtime\common\OutcomeVariable;

class ResultsService extends tao_models_classes_ClassService
{

    /**
     * a local cache (string)$callId=> (core_kernel_classes_Resource) $itemResult
     * @var array
     */
    private $cacheItemResult = array();
    
    /**
     * a local cache (string)identifier=> (core_kernel_classes_Resource) $deliveryResult
     * @var array
     */
    private $cacheDeliveryResult = array();
    
    /**
     * (non-PHPdoc)
     * 
     * @see tao_models_classes_ClassService::getRootClass()
     */
    public function getRootClass()
    {
        return new core_kernel_classes_Class(TAO_DELIVERY_RESULT);
    }

    /**
     * return all variable for taht deliveryResults (uri identifiers)
     *
     * @access public
     * @author Joel Bout, <joel.bout@tudor.lu>
     * @param
     *            Resource deliveryResult
     * @param
     *            core_kernel_classes_Class to restrict to a specific class of variables
     * @param
     *            boolean flat a falt array is returned or a structured delvieryResult-ItemResult-Variable
     * @return array
     */
    public function getVariables(core_kernel_classes_Resource $deliveryResult, $variableClass = null, $flat = true)
    {
        $variables = array();
        // this service is slow due to the way the data model design
        // if the delvieryResult related execution is finished, the data is stored in cache.
        $serial = 'deliveryResultVariables';
        if ($variableClass != null) {
            $serial .= $variableClass->getUri();
        }
        if (common_cache_FileCache::singleton()->has($serial)) {
            $variables = common_cache_FileCache::singleton()->get($serial);
        } else {
            foreach ($this->getItemResultsFromDeliveryResult($deliveryResult) as $itemResult) {
                $itemResultVariables = $this->getVariablesFromItemResult($itemResult, $variableClass);
                $itemResultUri = $itemResult->getUri();
                $variables[$itemResultUri] = $itemResultVariables;
            }
            // overhead for cache handling, the data is stored only when the underlying deliveryExecution is finished
            try {
                $executionIdentifier = $deliveryResult->getUniquePropertyValue(new core_kernel_classes_Property(PROPERTY_IDENTIFIER));
                $status = $executionIdentifier->getUniquePropertyValue(new core_kernel_classes_Property(PROPERTY_DELVIERYEXECUTION_STATUS));
                if ($status->getUri() == INSTANCE_DELIVERYEXEC_FINISHED) {
                    common_cache_FileCache::singleton()->put($variables, $serial);
                }
            } catch (common_Exception $e) {
                common_Logger::i("List of variables of results of " . $deliveryResult->getUri() . " could not be reliable cached due to an unfinished execution");
            }
        }
        if ($flat) {
            $returnValue = array();
            foreach ($variables as $itemResultVariables) {
                $returnValue = array_merge($itemResultVariables, $returnValue);
            }
        } else {
            $returnValue = $variables;
        }
        
        return (array) $returnValue;
    }

    /**
     *
     * @param core_kernel_classes_Resource Resource deliveryResult
     * @param core_kernel_classes_Class to restrict to a specific class of variables
     * @return array
     */
    public function getVariablesFromItemResult(core_kernel_classes_Resource $itemResult, $variableClass = null)
    {
        if (is_null($variableClass)) {
            $variableClass = new core_kernel_classes_Class(TAO_RESULT_VARIABLE);
        }
        $itemResultVariables = $variableClass->searchInstances(array(
            PROPERTY_RELATED_ITEM_RESULT => $itemResult->getUri()
        ), array(
            'recursive' => true,
            'like' => false
        ));
        return $itemResultVariables;
    }

    /**
     * Return the corresponding delivery
     * 
     * @param core_kernel_classes_Resource $deliveryResult            
     * @return core_kernel_classes_Resource delviery
     * @author Patrick Plichart, <patrick@taotesting.com>
     */
    public function getDelivery(core_kernel_classes_Resource $deliveryResult)
    {
        return $deliveryResult->getUniquePropertyValue(new core_kernel_classes_Property(PROPERTY_RESULT_OF_DELIVERY));
    }

    /**
     * Returns all itemResults related to the delvieryResult
     * 
     * @param core_kernel_classes_Resource $deliveryResult            
     * @return array core_kernel_classes_Resource
     *        
     */
    public function getItemResultsFromDeliveryResult(core_kernel_classes_Resource $deliveryResult)
    {
        $type = new core_kernel_classes_Class(ITEM_RESULT);
        $returnValue = $type->searchInstances(array(
            PROPERTY_RELATED_DELIVERY_RESULT => $deliveryResult->getUri()
        ), array(
            'like' => false
        ));
        return $returnValue;
    }

    /**
     *
     * @param core_kernel_classes_Resource $variable            
     * @return common_Object
     */
    public function getItemResultFromVariable(core_kernel_classes_Resource $variable)
    {
        $relatedItemResult = new core_kernel_classes_Property(PROPERTY_RELATED_ITEM_RESULT);
        $itemResult = $variable->getUniquePropertyValue($relatedItemResult);
        return $itemResult;
    }

    /**
     *
     * @param core_kernel_classes_Resource $itemResult            
     * @return common_Object
     */
    public function getItemFromItemResult(core_kernel_classes_Resource $itemResult)
    {
        $relatedItem = new core_kernel_classes_Property(PROPERTY_RELATED_ITEM);
        $item = $itemResult->getUniquePropertyValue($relatedItem);
        return $item;
    }

    /**
     *
     * @param core_kernel_classes_Resource $variable            
     * @return common_Object
     */
    public function getItemFromVariable(core_kernel_classes_Resource $variable)
    {
        return $this->getItemFromItemResult($this->getItemResultFromVariable($variable));
    }

    /**
     *
     * @param unknown $variableUri            
     * @return common_Object
     *
     */
    public function getVariableValue($variableUri)
    {
        $variable = new core_kernel_classes_Resource($variableUri);
        $return = $variable->getUniquePropertyValue(new core_kernel_classes_Property(RDF_VALUE));
        
        return $return;
    }

    /**
     *
     * @param unknown $variableUri            
     * @return common_Object
     */
    public function getVariableBaseType($variableUri)
    {
        $variable = new core_kernel_classes_Resource($variableUri);
        return $variable->getUniquePropertyValue(new core_kernel_classes_Property(PROPERTY_VARIABLE_BASETYPE));
    }

    private function getItemInfo($item, $undefinedStr) {
        if (get_class($item) == "core_kernel_classes_Literal") {
            $id = $item->__toString();
            $label = $item->__toString();
            $model = $undefinedStr;
        } elseif (get_class($item) == "core_kernel_classes_Resource") {
            $id = $item->getUri();
            $label = $item->getLabel();

            try {
                $modelProperty = $item->getUniquePropertyValue(new core_kernel_classes_Property(TAO_ITEM_MODEL_PROPERTY));
                $model = $modelProperty->getLabel();
            } catch (common_Exception $e) { // a resource but unknown
                $model = $undefinedStr;
            }
        } else {
            $id = $undefinedStr;
            $label = $undefinedStr;
            $model = $undefinedStr;
        }

        return array($id, $label, $model);
    }

    private function createVariableObject($variable, &$varType, &$identifier, &$epoch, &$outcome, &$numberOfResponseVariables, &$numberOfOutcomeVariables) {
        // retrieve the type of the variable
        $class = current($variable->getTypes());
        $varType = $class->getUri();

        // common properties to all variables
        $properties = array(
            PROPERTY_IDENTIFIER,
            PROPERTY_VARIABLE_CARDINALITY,
            PROPERTY_VARIABLE_EPOCH
        );

        // specific property Response Variable
        switch ($varType) {
            case CLASS_RESPONSE_VARIABLE:
                ++$numberOfResponseVariables;
                $properties[] = PROPERTY_RESPONSE_VARIABLE_CORRECTRESPONSE;
                $objVariable = new taoResultServer_models_classes_ResponseVariable();
                break;
            case CLASS_OUTCOME_VARIABLE:
                ++$numberOfOutcomeVariables;
                $objVariable = new taoResultServer_models_classes_OutcomeVariable();
                break;
            case CLASS_TRACE_VARIABLE;
                $objVariable = new taoResultServer_models_classes_TraceVariable();
                break;
            default:
                throw new common_exception_Error("The variable class is not supported");
        }

        $baseType = current($variable->getPropertyValues(new core_kernel_classes_Property(PROPERTY_VARIABLE_BASETYPE)));
        if ($baseType == "file") {
            $variableDescription = $variable->getPropertiesValues($properties);
            $outcome = false;
        } else {
            $properties[] = RDF_VALUE;
            $variableDescription = $variable->getPropertiesValues($properties);
            $outcome = array(base64_decode(current($variableDescription[RDF_VALUE])));
        }

        $identifier = current($variableDescription[PROPERTY_IDENTIFIER]);
        $epoch = current($variableDescription[PROPERTY_VARIABLE_EPOCH])->__toString();

        $objVariable->setIdentifier($identifier);
        $objVariable->setBaseType($baseType);
        $objVariable->setCardinality(current($variableDescription[PROPERTY_VARIABLE_CARDINALITY]));
        $objVariable->setEpoch(tao_helpers_Date::displayeDate(tao_helpers_Date::getTimeStamp($epoch), tao_helpers_Date::FORMAT_VERBOSE));
        if ($varType == CLASS_RESPONSE_VARIABLE) {
            $objVariable->setCorrectResponse(current($variableDescription[PROPERTY_RESPONSE_VARIABLE_CORRECTRESPONSE]));
        }


        return $objVariable;
    }

    /**
     * prepare a data set as an associative array, service intended to populate gui controller
     *
     * @param string $filter
     *            'lastSubmitted', 'firstSubmitted'
     */
    public function getItemVariableDataFromDeliveryResult(core_kernel_classes_Resource $deliveryResult, $filter)
    {
        $undefinedStr = __('unknown'); // some data may have not been submitted
        
        $itemResults = $this->getItemResultsFromDeliveryResult($deliveryResult);

        $variablesByItem = array();
        $numberOfResponseVariables = 0;
        $numberOfCorrectResponseVariables = 0;
        $numberOfInCorrectResponseVariables = 0;
        $numberOfUnscoredResponseVariables = 0;
        $numberOfOutcomeVariables = 0;
        $epoch = '';
        $variableIdentifier = '';
        $variableType = '';
        $outcomeValue = '';

        foreach ($itemResults as $itemResult) {

            try {
                $relatedItem = $this->getItemFromItemResult($itemResult);
            } catch (common_Exception $e) {
                $relatedItem = null;
            }

            list($itemIdentifier, $itemLabel, $itemModel) = $this->getItemInfo($relatedItem, $undefinedStr);

            $variables = array();
            foreach ($this->getVariablesFromItemResult($itemResult) as $variable) {

                $variableInst = $this->createVariableObject($variable, $variableType, $variableIdentifier, $epoch, $outcomeValue, $numberOfResponseVariables, $numberOfOutcomeVariables);

                if ($variableType == CLASS_RESPONSE_VARIABLE) {
                    $correctResponse = $variableInst->getCorrectResponse();
                    if (get_class($correctResponse) == 'core_kernel_classes_Resource') {
                        if ($correctResponse->getUri() == GENERIS_TRUE) {
                            ++$numberOfCorrectResponseVariables;
                            $response = "correct";
                        } else {

                            if($correctResponse->getUri() == GENERIS_FALSE){
                                ++$numberOfInCorrectResponseVariables;
                                $response = "incorrect";
                            }
                            else{
                                ++$numberOfUnscoredResponseVariables;
                                $response = "unscored";
                            }

                        }
                    } else {
                        ++$numberOfUnscoredResponseVariables;
                        $response = "unscored";
                    }
                } else {
                    $response = false;
                }

                $variables[$variableType][$variableIdentifier->__toString()][$epoch] = array(
                    'variable'  => $variableInst,
                    'outcome'   => $outcomeValue,
                    'isCorrect' => $response,
                    'uri'       => $variable->getUri()
                );

            }

            $variablesByItem[$itemIdentifier] = array(
                'itemModel'  => $itemModel,
                'label'      => $itemLabel,
                'sortedVars' => $variables
            );
        }

        // sort by epoch and filter
        foreach ($variablesByItem as $itemIdentifier => $itemVariables) {
            foreach ($itemVariables['sortedVars'] as $variableType => $variables) {
                foreach ($variables as $variableIdentifier => $observation) {
                    
                    uksort($variablesByItem[$itemIdentifier]['sortedVars'][$variableType][$variableIdentifier], "self::sortTimeStamps");
                    
                    switch ($filter) {
                        case "lastSubmitted":
                            {
                                $variablesByItem[$itemIdentifier]['sortedVars'][$variableType][$variableIdentifier] = array(
                                    array_pop($variablesByItem[$itemIdentifier]['sortedVars'][$variableType][$variableIdentifier])
                                );
                                break;
                            }
                        case "firstSubmitted":
                            {
                                $variablesByItem[$itemIdentifier]['sortedVars'][$variableType][$variableIdentifier] = array(
                                    array_shift($variablesByItem[$itemIdentifier]['sortedVars'][$variableType][$variableIdentifier])
                                );
                                break;
                            }
                    }
                }
            }
        }

        return array(
            "nbResponses" => $numberOfResponseVariables,
            "nbCorrectResponses" => $numberOfCorrectResponseVariables,
            "nbIncorrectResponses" => $numberOfInCorrectResponseVariables,
            "nbUnscoredResponses" => $numberOfUnscoredResponseVariables,
            "data" => $variablesByItem
        );
    }
    /**
     * 
     * @param unknown $a
     * @param unknown $b
     * @return number
     */
    public static function sortTimeStamps($a, $b) {
        list($usec, $sec) = explode(" ", $a);
        $floata = ((float) $usec + (float) $sec);
        list($usec, $sec) = explode(" ", $b);
        $floatb = ((float) $usec + (float) $sec);
        //common_Logger::i($a." ".$floata);
        //common_Logger::i($b. " ".$floatb);
        //the callback is expecting an int returned, for the case where the difference is of less than a second
        //intval(round(floatval($b) - floatval($a),1, PHP_ROUND_HALF_EVEN));
        if ((floatval($floata) - floatval($floatb)) > 0) {
            return 1;
        } elseif ((floatval($floata) - floatval($floatb)) < 0) {
            return -1;
        } else {
            return 0;
        }
    }

    /**
     * return all variables linked to the delviery result and that are not linked to a particular itemResult
     *
     * @param core_kernel_classes_Resource $deliveryResult            
     * @return array An array of OutcomeVariable
     */
    public function getVariableDataFromDeliveryResult(core_kernel_classes_Resource $deliveryResult)
    {
        $returnValue = array();
        
        $variableClass = new core_kernel_classes_Class(TAO_RESULT_VARIABLE);
        
        $variables = $variableClass->searchInstances(array(
            PROPERTY_RELATED_DELIVERY_RESULT => $deliveryResult->getUri()
        ), array(
            'recursive' => true,
            'like' => false
        ));
        foreach ($variables as $variable) {

            $variableDescription = $variable->getPropertiesValues(
                array(
                    PROPERTY_IDENTIFIER,
                    RDF_VALUE,
                    PROPERTY_VARIABLE_CARDINALITY,
                    PROPERTY_VARIABLE_BASETYPE
                )
            );

            $returnValue[] = new OutcomeVariable(
                $variableDescription[PROPERTY_IDENTIFIER][0]->__toString(),
                Cardinality::getConstantByName(
                    $variableDescription[PROPERTY_VARIABLE_CARDINALITY][0]->__toString()
                ),
                BaseType::getConstantByName(
                    current($variableDescription[PROPERTY_VARIABLE_BASETYPE])
                ),
                new Float((float) base64_decode(current($variableDescription[RDF_VALUE])))
            );

        }

        return $returnValue;
    }

    /**
     * returns the test taker related to the delivery
     *
     * @author Patrick Plichart, <patrick.plichart@taotesting.com>
     */
    public function getTestTaker(core_kernel_classes_Resource $deliveryResult)
    {
        $propResultOfSubject = new core_kernel_classes_Property(PROPERTY_RESULT_OF_SUBJECT);
        return $deliveryResult->getUniquePropertyValue($propResultOfSubject);
    }

    /**
     *
     * @param string $deliveryResultIdentifier            
     * @return core_kernel_classes_resource
     * @throws common_exception_Error
     */
    public function storeDeliveryResult($deliveryResultIdentifier = null)
    {
        $deliveryResultClass = new core_kernel_classes_Class(TAO_DELIVERY_RESULT);
        if (is_null($deliveryResultIdentifier)) {
            $id = uniqid();
            $deliveryResult = $deliveryResultClass->createInstanceWithProperties(array(
                RDFS_LABEL => '(' . $id . ')',
                PROPERTY_IDENTIFIER => $id
            ));
            return $deliveryResult;
        }
        // an identifier is provided, look in the cache
        if (isset($this->cacheDeliveryResult[$deliveryResultIdentifier])) {
            return $this->cacheDeliveryResult[$deliveryResultIdentifier];
        }
        
        $options = array(
            'like' => false,
            'recursive' => false
        );
        $deliveryResults = $deliveryResultClass->searchInstances(array(
            PROPERTY_IDENTIFIER => $deliveryResultIdentifier
        ), $options);
        
        if (count($deliveryResults) > 1) {
            throw new common_exception_Error('More than 1 deliveryResult for the corresponding Id ' . $deliveryResultIdentifier);
        } elseif (count($deliveryResults) == 1) {
            $returnValue = array_shift($deliveryResults);
            $this->cacheDeliveryResult[$deliveryResultIdentifier] = $returnValue;
            common_Logger::d('found Delivery Result after search for ' . $deliveryResultIdentifier);
        } else {
            $returnValue = $deliveryResultClass->createInstanceWithProperties(array(
                RDFS_LABEL => '(' . $deliveryResultIdentifier . ')',
                PROPERTY_IDENTIFIER => $deliveryResultIdentifier
            ));
            $this->cacheDeliveryResult[$deliveryResultIdentifier] = $returnValue;
        }
        return $returnValue;
    }

    /**
     *
     * @param
     *            string testTakerIdentifier (uri recommended)
     */
    public function storeTestTaker(core_kernel_classes_Resource $deliveryResult, $testTakerIdentifier)
    {
        $propResultOfSubject = new core_kernel_classes_Property(PROPERTY_RESULT_OF_SUBJECT);
        $deliveryResult->editPropertyValues($propResultOfSubject, $testTakerIdentifier);
        
        try {
            // if the delviery information is provided, update to a more meaningful delvieryResult Label
            $testTaker = new core_kernel_classes_Resource($testTakerIdentifier);
            $testTakerLabel = $testTaker->getLabel();
            $deliveryResult->setLabel($testTakerLabel . "-" . str_replace("-" . $testTakerLabel, "", $deliveryResult->getLabel()));
        } catch (common_Exception $e) {
            // the test taker to be referrd in the delivery Result does not exist (or the label is not stated)
        }
    }

    /**
     *
     * @param
     *            string deliveryIdentifier (uri recommended)
     */
    public function storeDelivery(core_kernel_classes_Resource $deliveryResult, $deliveryIdentifier)
    {
        $propResultOfDelivery = new core_kernel_classes_Property(PROPERTY_RESULT_OF_DELIVERY);
        $deliveryResult->editPropertyValues($propResultOfDelivery, $deliveryIdentifier);
        
        try {
            // if the delviery information is provided, update to a more meaningful delvieryResult Label
            $delivery = new core_kernel_classes_Resource($deliveryIdentifier);
            $deliveryLabel = $delivery->getLabel();
            $deliveryResult->setLabel(str_replace("-" . $deliveryLabel, "", $deliveryResult->getLabel()) . "-" . $deliveryLabel);
        } catch (Exception $e) {
            // the test taker to be referrd in the delivery Result does not exist (or the label is not stated)
        }
    }

    /**
     * Submit a specific Item Variable, (ResponseVariable and OutcomeVariable shall be used respectively for collected data and score/interpretation computation)
     * 
     * @param
     *            string test (uri recommended)
     * @param
     *            string item (uri recommended)
     * @param
     *            taoResultServer_models_classes_ItemVariable itemVariable
     * @param
     *            string callId an id for the item instanciation
     */
    /* todo dependency due to object */
    public function storeItemVariable(core_kernel_classes_Resource $deliveryResult, $test, $item, taoResultServer_models_classes_Variable $itemVariable, $callId)
    {
        $start = microtime();
        $itemResult = $this->getItemResult($deliveryResult, $callId, $test, $item);
        $end = microtime();
        common_Logger::i(' Time to retrieve container for his execution of item ' . ($end - $start));
        common_Logger::i(' createInstanceWithProperties : ' . $itemVariable->getIdentifier());
        $storedVariable = $this->storeVariable($itemVariable, $itemResult->getUri());
        $storageEnd = microtime();
        common_Logger::i('     Time Needed for this variable' . ($storageEnd - $end));
        
        return $storedVariable;
    }

    /**
     *
     * @param unknown $itemVariable            
     * @param
     *            string uri of the related itemResult - optionnal
     * @throws common_exception_Error
     * @return core_kernel_classes_Resource
     */
    private function storeVariable($itemVariable, $relatedItemResult = null)
    {
        switch (get_class($itemVariable)) {
            case "taoResultServer_models_classes_OutcomeVariable":
                {
                    $outComeVariableClass = new core_kernel_classes_Class(CLASS_OUTCOME_VARIABLE);
                    $properties = array(
                        PROPERTY_IDENTIFIER => $itemVariable->getIdentifier(),
                        PROPERTY_VARIABLE_CARDINALITY => $itemVariable->getCardinality(),
                        PROPERTY_VARIABLE_BASETYPE => $itemVariable->getBaseType(),
                        PROPERTY_OUTCOME_VARIABLE_NORMALMAXIMUM => $itemVariable->getNormalMaximum(),
                        PROPERTY_OUTCOME_VARIABLE_NORMALMINIMUM => $itemVariable->getNormalMinimum(),
                        
                        // the php obect is stored as such (serialized),
                        // the value itselfs is being base64encoded as a member fo that object
                        RDF_VALUE => base64_encode($itemVariable->getValue()),
                        PROPERTY_VARIABLE_EPOCH => (($itemVariable->isSetEpoch())) ? $itemVariable->getEpoch() : microtime()
                    );
                    if (isset($relatedItemResult)) {
                        $properties[PROPERTY_RELATED_ITEM_RESULT] = $relatedItemResult;
                    }
                    $returnValue = $outComeVariableClass->createInstanceWithProperties($properties);
                    
                    break;
                }
            case "taoResultServer_models_classes_ResponseVariable":
                {
                    $responseVariableClass = new core_kernel_classes_Class(CLASS_RESPONSE_VARIABLE);
                    if (is_null($itemVariable->getCorrectResponse())) {
                        $isCorrect = "";
                    } else {
                        if ($itemVariable->getCorrectResponse()) {
                            $isCorrect = GENERIS_TRUE;
                        } else {
                            $isCorrect = GENERIS_FALSE;
                        }
                    }
                    $properties = array(
                        PROPERTY_IDENTIFIER => $itemVariable->getIdentifier(),
                        PROPERTY_VARIABLE_CARDINALITY => $itemVariable->getCardinality(),
                        PROPERTY_VARIABLE_BASETYPE => $itemVariable->getBaseType(),
                        // put as rdf#boolean
                        PROPERTY_RESPONSE_VARIABLE_CORRECTRESPONSE => $isCorrect,
                        // PROPERTY_RESPONSE_VARIABLE_CANDIDATERESPONSE=> $itemVariable->getCandidateResponse(),
                        RDF_VALUE => base64_encode($itemVariable->getCandidateResponse()),
                        PROPERTY_VARIABLE_EPOCH => (($itemVariable->isSetEpoch())) ? $itemVariable->getEpoch() : microtime()
                    );
                    if (isset($relatedItemResult)) {
                        $properties[PROPERTY_RELATED_ITEM_RESULT] = $relatedItemResult;
                    }
                    $returnValue = $responseVariableClass->createInstanceWithProperties($properties);
                    break;
                }
            case "taoResultServer_models_classes_TraceVariable":
                {
                    $traceVariableClass = new core_kernel_classes_Class(CLASS_TRACE_VARIABLE);
                    
                    $properties = array(
                        PROPERTY_IDENTIFIER => $itemVariable->getIdentifier(),
                        PROPERTY_VARIABLE_CARDINALITY => $itemVariable->getCardinality(),
                        PROPERTY_VARIABLE_BASETYPE => $itemVariable->getBaseType(),
                        RDF_VALUE => base64_encode($itemVariable->getTrace()), // todo store a file
                        PROPERTY_VARIABLE_EPOCH => (($itemVariable->isSetEpoch())) ? $itemVariable->getEpoch() : microtime()
                    );
                    if (isset($relatedItemResult)) {
                        $properties[PROPERTY_RELATED_ITEM_RESULT] = $relatedItemResult;
                    }
                    $returnValue = $traceVariableClass->createInstanceWithProperties($properties);
                    
                    break;
                }
            default:
                {
                    throw new common_exception_Error("The variable class is not supported");
                    break;
                }
        }
        return $returnValue;
    }

    /**
     *
     * @param core_kernel_classes_Resource $deliveryResult            
     * @param unknown $test            
     * @param taoResultServer_models_classes_Variable $itemVariable            
     * @param unknown $callId            
     */
    public function storeTestVariable(core_kernel_classes_Resource $deliveryResult, $test, taoResultServer_models_classes_Variable $itemVariable, $callId)
    {
        $storedVariable = $this->storeVariable($itemVariable);
        $storedVariable->setPropertyValue(new core_kernel_classes_Property(PROPERTY_RELATED_DELIVERY_RESULT), $deliveryResult->getUri());
    }

    /**
     *
     * @param core_kernel_classes_Resource $deliveryResult            
     * @param unknown $callId            
     * @param unknown $test            
     * @param unknown $item            
     * @throws common_exception_Error
     * @return Ambigous <mixed, core_kernel_classes_Resource>
     */
    public function getItemResult(core_kernel_classes_Resource $deliveryResult, $callId, $test, $item)
    {
        
        // check first from the local cache
        if (isset($this->cacheItemResult[$callId])) {
            return $this->cacheItemResult[$callId];
        }
        
        $itemResultsClass = new core_kernel_classes_Class(ITEM_RESULT);
        $itemResults = $itemResultsClass->searchInstances(array(
            PROPERTY_IDENTIFIER => $callId
        ), array(
            "like" => false
        ));
        if (count($itemResults) > 1) {
            throw new common_exception_Error('More then 1 itemResult for the corresponding Id ' . $deliveryResultIdentifier);
        } elseif (count($itemResults) == 1) {
            $returnValue = array_shift($itemResults);
            common_Logger::d('found Item Result after search for ' . $callId);
        } else {
            $returnValue = $itemResultsClass->createInstanceWithProperties(array(
                RDFS_LABEL => $callId,
                PROPERTY_IDENTIFIER => $callId,
                PROPERTY_RELATED_ITEM => $item,
                PROPERTY_RELATED_TEST => $test,
                PROPERTY_RELATED_DELIVERY_RESULT => $deliveryResult->getUri()
            ));
        }
        $this->cacheItemResult[$callId] = $returnValue;
        return $returnValue;
    }

    /**
     * Short description of method deleteResult
     */
    public function deleteResult(core_kernel_classes_Resource $result)
    {
        $returnValue = (bool) false;
        
        if (! is_null($result)) {
            
            $itemResults = $this->getItemResultsFromDeliveryResult($result);
            $variables = $this->getVariables($result);
            foreach ($itemResults as $itemResult) {
                $itemResult->delete();
            }
            foreach ($variables as $variable) {
                $variable->delete();
            }
            
            $returnValue = $result->delete();
        }
        
        return (bool) $returnValue;
    }

    /**
     * Short description of method deleteResultClass
     */
    public function deleteResultClass(core_kernel_classes_Class $clazz)
    {
        $returnValue = (bool) false;
        
        if (! is_null($clazz)) {
            $returnValue = $clazz->delete();
        }
        return (bool) $returnValue;
    }


    /**
     * Retrieves all score variables pertaining to the deliveryResult
     *
     * @access public
     * @author Patrick Plichart, <patrick.plichart@taotesting.com>
     * @param  Resource deliveryResult
     * @return array
     */
    public function getScoreVariables(core_kernel_classes_Resource $deliveryResult) {
        return $this->getVariables($deliveryResult, new core_kernel_classes_Class(CLASS_OUTCOME_VARIABLE));
    }

    /**
     * Retrieves information about the variable, including or not the related item $getItem (slower)
     * 
     * @access public
     * @author Patrick Plichart, <patrick.plichart@taotesting.com>
     * @param  Resource variable
     * @param  bool getItem retireve associated item reference
     * @return array simple associative
     */
    public function getVariableData(core_kernel_classes_Resource $variable, $getItem = false) {
        $returnValue = array();
        $baseTypes = $variable->getPropertyValues(new core_kernel_classes_Property(PROPERTY_VARIABLE_BASETYPE));
        $baseType = current($baseTypes);
        if ($baseType != "file") {
            $propValues = $variable->getPropertiesValues(array(
                PROPERTY_IDENTIFIER,
                PROPERTY_VARIABLE_EPOCH,
                RDF_VALUE,
                PROPERTY_VARIABLE_CARDINALITY,
                PROPERTY_VARIABLE_BASETYPE
            ));
            $returnValue["value"] = (string) base64_decode(current($propValues[RDF_VALUE]));
        } else {
            $propValues = $variable->getPropertiesValues(array(
                PROPERTY_IDENTIFIER,
                PROPERTY_VARIABLE_EPOCH,
                PROPERTY_VARIABLE_CARDINALITY,
                PROPERTY_VARIABLE_BASETYPE
            ));
            $returnValue["value"] = "";
        }
        $returnValue["identifier"] = current($propValues[PROPERTY_IDENTIFIER])->__toString();
        $class =  current($variable->getTypes());    
        $returnValue["type"]= $class;
        $returnValue["epoch"] = current($propValues[PROPERTY_VARIABLE_EPOCH])->__toString();
        if (count($propValues[PROPERTY_VARIABLE_CARDINALITY]) > 0) {
            $returnValue["cardinality"] = current($propValues[PROPERTY_VARIABLE_CARDINALITY])->__toString();
        }
        if (count($propValues[PROPERTY_VARIABLE_BASETYPE]) > 0) {
            $returnValue["basetype"] = current($propValues[PROPERTY_VARIABLE_BASETYPE])->__toString();
        }
        if ($getItem) {
            $returnValue["item"] = $this->getItemFromVariable($variable);
        }
        return (array) $returnValue;
    }
    

    /**
     * To be reviewed as it implies a dependency towards taoSubjects
     * @param core_kernel_classes_Resource $deliveryResult
     */
    public function getTestTakerData(core_kernel_classes_Resource $deliveryResult) {
        $testTaker = $this->gettestTaker($deliveryResult);
        if (get_class($testTaker) == 'core_kernel_classes_Literal') {
            return $testTaker;
        } else {
            $propValues = $testTaker->getPropertiesValues(array(
                RDFS_LABEL,
                PROPERTY_USER_LOGIN,
                PROPERTY_USER_FIRSTNAME,
                PROPERTY_USER_LASTNAME,
                PROPERTY_USER_MAIL,
            ));
        }
        return $propValues;
    }
}
?>