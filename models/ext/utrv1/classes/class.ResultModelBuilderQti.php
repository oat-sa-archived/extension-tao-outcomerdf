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
/* 
 * 
*/
/**
 * create class with all properties that are included in the paramaters.
 * This class is used only in the first time or to re-create the result model.
 *
 * @author Younes Djaghloul, CRP Henri Tudor
 * @package Result
 */
require_once(dirname(__FILE__) . "/../../../../includes/raw_start.php");


//define ("$RESULT_NS",'http://127.0.0.1/middleware/demo.rdf#');
define ('RESULT_MODEL',$_SERVER['DOCUMENT_ROOT'].'/taoResults/models/ext/utrv1/classes/modelQti.xml');

/**
 * create class with all properties that are included in the paramaters.
 * This class is used only in the first time or to re-create the result model.
 *
 * @access public
 * @author Younes Djaghloul, CRP Henri Tudor
 * @package Result
 */

class ResultModelBuilderQti {

    public $uriRootClassOfResult ="http://www.tao.lu/Ontologies/TAOResult.rdf#Result";
    /**
     * Based on a specific model in xml file, this method creat the model of
     *
     * @access public
     * @author Younes Djaghloul, CRP Henri Tudor
     * @return void
     */
    public function createModel() {
        $domResult = new DOMDocument();
        $domResult->load(RESULT_MODEL);
        //get class info

//http://www.tao.lu/datatypes/WidgetDefinitions.rdf#HTMLArea

        define('API_LOGIN',SYS_USER_LOGIN);
        define('API_PASSWORD',SYS_USER_PASS);
        $userService = core_kernel_users_Service::singleton();
		$userService->login(API_LOGIN, API_PASSWORD, new core_kernel_classes_Class('http://www.tao.lu/Ontologies/TAO.rdf#TaoManagerRole'));

        //Get the NameSpace of in order to add the instances
        $RESULT_NS = "http://www.tao.lu/Ontologies/TAOResult.rdf"; // put name space of TAO
        $RESULT_NS = $RESULT_NS.'#';

        $listClasses = $domResult->getElementsByTagName("class");

        foreach($listClasses as $class) {
            //labelClass = $class->getAttribute("label");
            $uriClass = $class->getAttribute("uri");
            //make the case upper, more
            $uriClass = strtoupper($uriClass);

            $labelClass = "".$uriClass;

            //For the actual class we buuil the array of properties
            $listProperties = $class->getElementsByTagName("property");
            $t= array();
            foreach($listProperties as $propertyDescription) {
                $propDescription=array();

                $uriProp = $propertyDescription->getAttribute("uri");
                //Make the case upper
                $uriProp = strtoupper($uriProp);

                $labelProp =$propertyDescription->getAttribute("label");

                $propDescription['labelProperty']=$uriProp;
                $propDescription['commentProperty']='comment '.$uriProp;
                $propDescription['uriProperty']='#'.$uriProp;
                $t[]=$propDescription;


            }
            //Create the class
            $this->createClassWithProperties($this->uriRootClassOfResult, "#".$uriClass, $labelClass, '$comment', $t);

        }
        //add Range properties


        //for subject antd other classes
        $prop = new core_kernel_classes_Property($RESULT_NS."TAO_SUBJECT_ID");
        $class = new core_kernel_classes_Class(TAO_SUBJECT_CLASS);

        $prop->setRange($class);


        $prop = new core_kernel_classes_Property($RESULT_NS."TAO_ITEM_ID");
        $class = new core_kernel_classes_Class(TAO_TEST_CLASS);
        $prop->setRange($class);


        $prop = new core_kernel_classes_Property($RESULT_NS."TAO_TEST_ID");
        $class = new core_kernel_classes_Class(TAO_ITEM_CLASS);
        $prop->setRange($class);

        $prop = new core_kernel_classes_Property($RESULT_NS."TAO_DELIVERY_ID");
        $class = new core_kernel_classes_Class(TAO_DELIVERY_CLASS);
        $prop->setRange($class);





    }
    /**
     * Creates a class with all its properties and integrate them in TAO as
     * of a root class that can be chosen.
     *
     * @access public
     * @author Younes Djaghloul, CRP Henri Tudor
     * @param  String $uriRootClass
     * @param  String $uriClass
     * @param  String $labelClass
     * @param  String $commentClass
     * @param  Collection $tabProperties
     * @return void
     */

    public function createClassWithProperties($uriRootClass,$uriClass,$labelClass,$commentClass,$tabProperties) {
        //        $r = new RegCommon();
        //        $r->regConnect();

        //create the class
        $rdfClass = new core_kernel_classes_Class(RDF_CLASS);
        $resourceClass = $rdfClass->createInstance($labelClass,$commentClass,$uriClass);
        //now, create the php object Class to be used in trhe creation of the properties
        //This class should be linked to the URI already created
        $trClass =new core_kernel_classes_Class($resourceClass->uriResource);
        //now we have the php object linked to the class in the model
        //echo $resourceClass->uriResource . " <br>";

        //Create the property with a specific uri
        foreach( $tabProperties as $propDescription) {
            //get property infos
            $labelProperty = $propDescription['labelProperty'];
            $commentProperty =  $propDescription['commentProperty'];
            $uriProperty = $propDescription['uriProperty'];

            //Created the property with informations extracted

            $rdfProperty = new core_kernel_classes_Class(RDF_PROPERTY);
            $resourceProperty = $rdfProperty->createInstance($labelProperty,$commentProperty,$uriProperty);
            //Create the pho pbject property and link it with the uri
            $trProperty = new core_kernel_classes_Property($resourceProperty->uriResource);

            //http://www.tao.lu/datatypes/WidgetDefinitions.rdf#widget
            //http://www.tao.lu/datatypes/WidgetDefinitions.rdf#TextBox
            //put a widget for the property

            $widgetProp  = new core_kernel_classes_Property("http://www.tao.lu/datatypes/WidgetDefinitions.rdf#widget");
            $trProperty->setPropertyValue($widgetProp,"http://www.tao.lu/datatypes/WidgetDefinitions.rdf#TextBox");

            //Link the property with the class
            $trClass->setProperty($trProperty);


        }//end for each add properties
        //Put the class as subclass of root
        $rootResultClass = new core_kernel_classes_Class($uriRootClass);
        $trClass->setSubClassOf($rootResultClass);

        //end


    }
}

$p= new ResultModelBuilderQti();
//echo $_SERVER['DOCUMENT_ROOT']."generis/common/inc.extension.php";
$p->createModel();


?>
