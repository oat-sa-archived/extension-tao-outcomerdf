<?php
/*  
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
 * Copyright (c) 2002-2008 (original work) Public Research Centre Henri Tudor & University of Luxembourg (under the project TAO & TAO2);
 *               2008-2010 (update and modification) Deutsche Institut für Internationale Pädagogische Forschung (under the project TAO-TRANSFER);\n *               2009-2012 (update and modification) Public Research Centre Henri Tudor (under the project TAO-SUSTAIN & TAO-DEV);
 * 
 */
?>
<?php

/**
 * RegCommon provides the common methods to acces generis API in more suitable
 * in Table Builder context.
 *
 * @author Younes Djaghloul, <younes.djaghloul@tudor.lu>
 * @package Result
 */
require_once(dirname(__FILE__) . "/../../../../includes/raw_start.php");

/**
 * RegCommon provides the common methods to acces generis API in more suitable
 * in Table Builder context.
 *
 * @access public
 * @author Younes Djaghloul, <younes.djaghloul@tudor.lu>
 * @package Result
 */
class RegCommon {

    /**
     * This method permits to connect to a specific module of generis.
     *
     * @access public
     * @author Younes Djaghloul, <younes.djaghloul@tudor.lu>
     */
    public function regConnect() {
        
    }

    /**
     * to obtain a PHP object linked to the generis class, based on its URI
     *
     * @access public
     * @author Younes Djaghloul, <younes.djaghloul@tudor.lu>
     * @param  String $uriClass
     * @return java_lang_Object
     */
    public function linkClass($uriClass) {
        $trClass = new core_kernel_classes_Class($uriClass);
        return $trClass;
    }

    /**
     * to obtain a PHP object linked to the generis property, based on its URI
     *
     * @access public
     * @author Younes Djaghloul, <younes.djaghloul@tudor.lu>
     * @param  void $uriProperty
     * @return void
     */
    public function linkProperty($uriProperty) {
        $trProperty = new core_kernel_classes_Property($uriProperty);
        return $trProperty;
    }

    /**
     * get the label,the uri of the range and the label of the range of a
     * property
     *
     * @access public
     * @author Younes Djaghloul, <younes.djaghloul@tudor.lu>
     * @param  String $uriProperty
     * @return List
     */
    function trGetPropertyInfo($uriProperty) {
        $trProperty = new core_kernel_classes_Property($uriProperty);


        //extract most important properties for navigation
        $label = $trProperty->getLabel();

        $range = $trProperty->getRange();
        //var_dump($range);
        //S$dbWrapper->dbConnector->debug = false;
        //Create an object prop with the main important variable in our context.

        $prop->label = $label;

        $prop->uriRange = "";
        $prop->labelRange = "";

        if (!is_null($range)) {
            $prop->uriRange = $range->uriResource;
            $prop->labelRange = $range->getLabel();
        }

        //return the object
        return $prop;
    }

    /**
     * get properties of a particular class, return an array of uri properties
     * key with property info (Label + uriRange + labelRange)
     *
     * @access public
     * @author Younes Djaghloul, <younes.djaghloul@tudor.lu>
     * @param  String $uriClass
     * @return Collection
     */
    public function trGetProperties($uriClass) {
        //Link the class, and create a PHP object
        $trClass = $this->linkClass($uriClass);
        //get properties of the class based on the API
        $listProperties = $trClass->getProperties(true); //now.. it returnes an array of properities object
        //create only, an array with the URI of properties
        //filter the properties
        $listProperties = $this->trFilterProperties($listProperties);

        $listUri = array_keys($listProperties);

        //populate the array $listPropertiesUri with property infos
        $listPropertiesUri = array();

        foreach ($listUri as $uriProp) {
            //echo $uriProp. "<br>";
            //get the info of the property



            $infoProp = $this->trGetPropertyInfo($uriProp);
            //put the property infos in the array element
            $listPropertiesUri[$uriProp] = $infoProp;
        }

        return $listPropertiesUri;
    }

    /**
     * For a specific class, we get a list uf range classes,
     * ex : for class Student we will have a list that contains , Teacher,
     *
     * @access public
     * @author Younes Djaghloul, <younes.djaghloul@tudor.lu>
     * @param  String $uriClass
     * @return Collection
     */
    public function trGetRangeClasses($uriClass) {
        //initialize the array of classes
        $lc = array();
        $rangeClass = new stdClass();

        //get properties of the class
        $listProp = $this->trGetProperties($uriClass);

        //get the range of all properties
        foreach ($listProp as $uriProp => $infoProp) {

            $labelProp = $infoProp->label;
            $uriRange = $infoProp->uriRange;
            $labelRange = $infoProp->labelRange;
            //echo $labelProp." ". $labelRange." <br>";
            //Test if the range is class or not, if it is class, add it to the array
            //
            //Link to ressource
            //check if the uri is not empty
            if (common_Utils::isUri($uriRange)) {

                //$trResource = new core_kernel_classes_Resource($uriRange);
                //add this class to listClasses
                //the information are propertySourceUri, label of the class, the uri of the class is the key it self
                //IMPORTANT: we should instantiate a new object,
                $rangeClass = new stdClass();
                //get the values
                $rangeClass->propertySourceUri = $uriProp; // to keep the property responsable of this bridge
                $rangeClass->label = $labelRange;
                $rangeClass->uriClass = $uriRange;
                $rangeClass->type = "Rangey";
                //put in the array
                //Do a filter on class range in this version we delete all RDF classes and il uri is null ( This ocure some times !!!!)

                if ((substr($uriRange, 0, 17) != 'http://www.w3.org') and ($uriRange != '')) {

                    $lc[$uriRange] = $rangeClass; //->Label;
                }
            }

            // }//end of adding class's info
        }
        return $lc;
    }

    /**
     * get  classes of the given inctance;
     * The class of an instance is the value of RDF_TYPE of the instance.
     * Return structure  : $classValuesUri[$classUri]
     *
     * @access public
     * @author Younes Djaghloul, <younes.djaghloul@tudor.lu>
     * @param  String $uriInstance
     * @return Collection
     */
    public function trGetClassesOfInstance($uriInstance) {
        //We begin by create a resource PHP object
        $trResource = new core_kernel_classes_Resource($uriInstance);

        $cv = $trResource->getType();

        //put the uris as keys
        foreach ($cv as $type) {
            $classValuesUri[$type->uriResource] = 0;
        }
        //return an array of classes URI;
        //an instance can belong to several classes
        return $classValuesUri;
    }

    /**
     * This method provides only a set of properties that are not in filter,
     * It is important in the case of deleting all rdf properties
     *
     * @access public
     * @author Younes Djaghloul, <younes.djaghloul@tudor.lu>
     * @param  List $listProperties
     * @return List
     */
    function trFilterProperties($listProperties) {
        //http://www.w3.org/2000/01/rdf-schema#isDefinedBy
        //If the name space of the property is http://www.w3.org/2000/01/rdf-schema#isDefinedBy
        //Then delete from list
        $finalProp = $listProperties;
        $blockedProperties = array();

        $blockedProperties[] = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type';
        $blockedProperties[] = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#subject';
        $blockedProperties[] = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#predicate';
        $blockedProperties[] = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#object';
        $blockedProperties[] = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#value';
        $blockedProperties[] = 'http://www.w3.org/2000/01/rdf-schema#subPropertyOf';
        $blockedProperties[] = 'http://www.w3.org/2000/01/rdf-schema#comment';
        $blockedProperties[] = 'http://www.w3.org/2000/01/rdf-schema#seeAlso';
        $blockedProperties[] = 'http://www.w3.org/2000/01/rdf-schema#isDefinedBy';
        $blockedProperties[] = 'http://www.w3.org/2000/01/rdf-schema#member';

        foreach ($listProperties as $uri => $obj) {

            if (in_array($uri, $blockedProperties)) {
                //echo "jjjjj";
                unset($finalProp[$uri]);
            }
        }
        return $finalProp;
    }

    /**
     * This method provides tow arrays,
     * 1- The array od properties with, public function trGetProperties
     * 2- The array of classes range with public function
     *
     * @access public
     * @author Younes Djaghloul, <younes.djaghloul@tudor.lu>
     * @param  String $uriClass
     * @return Collection
     */
    public function trGetPropertiesAndClassesRange($uriClass) {

        $tp = $this->trGetProperties($uriClass);
        $tabRangeClasses = $this->trGetRangeClasses($uriClass);
        //filter
        $tabProperties = $this->trFilterProperties($tp);

        //put the two arrays in one.
        $tabAll["propertiesList"] = $tabProperties;
        $tabAll["rangeClassesList"] = $tabRangeClasses;

        return $tabAll;
    }

    /**
     * This method is the first invoked. according to a list of classes, it
     * the first list of properties and rangeClasses
     *
     * @access public
     * @author Younes Djaghloul, <younes.djaghloul@tudor.lu>
     * @param  Collection $arrayOfClasses
     * @return Collection
     */
    public function trExploreModel($arrayOfClasses) {
        $tabClass = $arrayOfClasses;
        $contextClass = array();
        //

        foreach ($tabClass as $uriClass => $info) {
            $contextClass[] = $this->trGetPropertiesAndClassesRange($uriClass);
        }
        return $contextClass;
    }

    /**
     * Provides a value of a specific property according to the path
     * ( path: a sequence of properties)  and the initial instance
     *
     * @access public
     * @author Younes Djaghloul, <younes.djaghloul@tudor.lu>
     * @param  String $instanceSourceUri
     * @param  String $pathOfProperties
     * @return java_lang_String
     */
    function trGetBridgePropertyValues($instanceSourceUri, $pathOfProperties) {
        //we begin by explode the path structure into an array
        //the path is created by the user with path builder in  the client side

        $pathOfPropertiesArray = explode('__', $pathOfProperties);
        //the array of uri of instances
        $listInstancesUri = array();
        //this array is used only to keep the list of the next uri instance of the actual path position
        $intermediateTabUri = array();
        //keep the real path
        $realPath = array();


        $valuePath['instance'] = $instanceSourceUri;
        $trResource = new core_kernel_classes_Resource($valuePath['instance']);

        //$valuePath['realPath'][] ='';
        $intermediateTabUri[] = $valuePath;
        foreach ($pathOfPropertiesArray as $propertyUri) {
            //link the resource
            $listInstancesUri = $intermediateTabUri;

            // after each progression in the path , we remove the array
            $intermediateTabUri = array();
            $valuesPath = array();
            //print_r($listInstancesUri);
            foreach ($listInstancesUri as $instanceUri) {
                $valuesPath = array();
                $trResource = new core_kernel_classes_Resource($instanceUri['instance']);
                // ça marche echo " -----the label is : ". $trResource->getLabel();
                //get the value of the property for this instance
                $values = $trResource->getPropertyValues(new core_kernel_classes_Property($propertyUri)); // get the array of values
                //print_r($values);
                //add the path of the actual instance on all step+1 instances

                foreach ($values as $val) {
                    //echo "<br>". $val;
                    $actualPV = array();
                    $actualPV['instance'] = $val;

                    // if $val is an URI then get the label, otherwise it is just a lateral
                    if (common_Utils::isUri($val)) {
                        $trResource = new core_kernel_classes_Resource($val);
                        $labelVal = $trResource->getLabel(); // this is the error, if t$val is not an uri, it return tyt !!!!!
                        //var_dump($labelVal);

                        if ($labelVal == NULL) {
                            $labelVal = "ERROR 404, Unavailable Resource";
                        }
                    } else {
                        $labelVal = (string) $val;
                    }


                    if (isset($instanceUri['realPath'])) {
                        $actualPV['realPath'] = $instanceUri['realPath'];
                    }
                    $actualPV['realPath'][] = $labelVal;
                    $valuesPath[] = $actualPV;
                }

                //this array containes the bridged uri of instances
                $intermediateTabUri = array_merge($intermediateTabUri, $valuesPath);
            }

            //break if the intermediateTab is void
            if (count($intermediateTabUri) == 0) {

                $intermediateTabUri = array();
                break;
            }
            //now the intermediateTab is complete, it will be the next input of the loop
        }//path
        //create the last value
        $finalValueTab = array();
        foreach ($intermediateTabUri as $vp) {

            //prepare values with label
            $finalValueTab[] = implode("->", $vp['realPath']);
            //in order to get only the value without all the path
        }

        $finalValue = implode('|$*', $finalValueTab); // $instanceUri;
        return $finalValue;
    }

    function trGetBridgePropertyValuesSOS($instanceSourceUri, $pathOfProperties) {
        //we begin by explode the path structure into an array
        //the path is created by the user with path builder in  the client side
        $pathOfPropertiesArray = explode('__', $pathOfProperties);

        //$instanceUri = $instanceSourceUri;
        //the array of uri of instances
        $listInstancesUri = array(); //[]=$instanceSourceUri;
        //this array is used only to keep the list of the next uri instance of the actual path position
        $intermediateTabUri = array();

        $actualPV = array();
        $actualPV['instance'] = $instanceSourceUri;
        $actualPV['realPath'] = array();

        $intermediateTabUri[] = $actualPV; //$instanceSourceUri;
        //keep the lreal path
        $realPath = array();

        // Into the path, step by step
        $countPath = count($pathOfProperties);
        $pathPosition = 0;

        foreach ($pathOfPropertiesArray as $propertyUri) {
            $pathPosition++;
            //for each uri instances in
            //link the resource

            $listInstancesUri = $intermediateTabUri;
            $intermediateTabUri = array();


            foreach ($listInstancesUri as $instanceUri) {
                $valuesPath = array();
                $trResource = new core_kernel_classes_Resource($instanceUri['instance']);
                //get the value of the property for this instance
                $values = $trResource->getPropertyValues(new core_kernel_classes_Property($propertyUri)); // get the array of values
                // ************* Added to keep the real path of the instance
                //
                //feed the valusPath

                $actualPV = array();
                foreach ($values as $val) {
                    $actualPV['instance'] = $val;

                    //prepare the label
                    $trResource = new core_kernel_classes_Resource($val);
                    $labelVal = $trResource->getLabel();

                    $actualPV['realPath'][] = $labelVal;
                    $valuesPath[] = $actualPV;
                }

                //this array containes the bridged uri of instances
                $intermediateTabUri = array_merge($intermediateTabUri, $valuesPath);
            }//instance
            //break if the intermediateTab is void
            if (count($intermediateTabUri) == 0) {
                $intermediateTabUri = array();
                break;
            }
            //now the intermediateTab is complete, it will be the next input of the loop
        }//path
        /*        echo "le real path"; */

        //print_r($intermediateTabUri);
        $finalValueTab = array();

        //create the last value
        foreach ($intermediateTabUri as $vp) {
            //prepare values with label

            $finalValueTab[] = implode("::", $vp['realPath']);
        }

        $finalValue = implode('|$*', $finalValueTab); // $instanceUri;
        return $finalValue;
    }

    /**
     * Provides the code of the current module in used,
     * This is very helpful to have a contextual behavior of UTR acording to the module
     *
     * @access public
     * @author Younes Djaghloul, <younes.djaghloul@tudor.lu>
     *
     * @return String , the code tof the module, one of these values
     * -taoItems
     * -taoTests
     * -taoSubjects
     * -taoGroups
     * -taoResults
     * -taoDelivery
     */
    public function getCurrentModule() {

        return context::getInstance()->getExtensionName();
    }

    //get the list of the subclasses
    public function getsubParentClasses($uriClass) {

        $listSubClasses = array();
        $listParentClasses = array();
        $listSubAndParentClasses = array();



        $classSource = new core_kernel_classes_class($uriClass);
        $subClasses = $classSource->getSubClasses(false);
        $parentClasses = $classSource->getParentClasses(false);
        /* print_r($listSubClasses);
          print_r($listParentClasses); */


        //create the array of subClasses    

        $lsc = array_keys($subClasses);
        foreach ($lsc as $uriSubClass) {
            $class = new core_kernel_classes_class($uriSubClass);
            $labelClass = $class->getLabel();
            $subClass = new stdClass();
            //get the values
            $subClass->label = $labelClass . '(sub)';
            $subClass->uriClass = $uriSubClass;
            $subClass->type = "subClass";
            $listSubClasses[$uriSubClass] = $subClass; //->Label;
        }

        //create the array of parentClasses        
        $lpc = array_keys($parentClasses);
        foreach ($lpc as $uriParentClass) {
            $class = new core_kernel_classes_class($uriParentClass);
            $labelClass = $class->getLabel();
            $parentClass = new stdClass();
            //get the values
            $parentClass->label = $labelClass . '(Parent)';
            $parentClass->uriClass = $uriParentClass;
            $parentClass->type = "parentClass";
            $listParentClasses[$uriParentClass] = $parentClass; //->Label;
        }

        //$listParentClasses = $classSource->getParentClasses(false);
        $listSubAndParentClasses['subClasses'] = $listSubClasses;
        $listSubAndParentClasses['parentClasses'] = $listParentClasses;

        return $listSubAndParentClasses;
    }

    //get the list of the subclasses
    public function getContextClasses($uriClass) {
        $listContextClasses = array();
        $listRange = array();
        $listSubAndParentClasses = array();

        $listRange = $this->trGetRangeClasses($uriClass);
        $listSubAndParentClasses = $this->getSubParentClasses($uriClass);

        /* $listContextClasses['listeRangeClasses']=$listRange;
          $listContextClasses['listSubParentClasses']=$listSubParent; */

        //$listContextClasses = array_merge($listRange, $listSubAndParentClasses['subClasses'],$listSubAndParentClasses['parentClasses']);
        $listContextClasses['rangeClasses'] = $listRange;
        $listContextClasses['subClasses'] = $listSubAndParentClasses['subClasses'];
        $listContextClasses['parentClasses'] = $listSubAndParentClasses['parentClasses'];
        
        return $listContextClasses;
    }

}

//End class
?>
