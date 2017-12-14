<?php
namespace Drupal\cncexproducts\Controller;

use Drupal\Core\Controller\ControllerBase;


/**
 * Provides route responses for the Example module.
 */
class CNCExProducts extends ControllerBase {

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function product_list() {

    $cncexsf = \Drupal::state()->get('cncexsf');
    $sfCred = json_decode($cncexsf);

    $defaultFile = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => $sfCred->cncexsf_wsdl]);;
    foreach ($defaultFile as $key => $image) {
      $fid = $key;
    }


        // Gather Objects
        $content = file_get_contents($sfCred->cncexsf_wsdl);
        $xml = simplexml_load_string($content);
        $json = json_encode($xml);
        $array = json_decode($json,TRUE);

        $objects = [];
        foreach ($array['types']['schema'] as $key => $value) {
          foreach ($value as $vkey => $vvalue) {
           if ($vkey == 'complexType') {
              foreach ($value['complexType'] as $ckey => $cvalue) {
                foreach ($cvalue as $attr => $name) {
                  if (strlen($name['name']) > 0) {
                    $objects[] = $name['name'];
                  }
                }
              }
            }
          }
        }


        if (isset($sfCred)) {
            $SFbuilder = new \Phpforce\SoapClient\ClientBuilder(
              $sfCred->cncexsf_wsdl,
              $sfCred->cncexsf_user,
              $sfCred->cncexsf_pass . $sfCred->cncexsf_api
            );
          $client = $SFbuilder->build();

         try {

          $return = [];

          $objects = ['Product2' => 'Id', 'Machine_Photo__c' => 'Machine__c'];

          foreach ($objects as $table => $field) {
            $sfFields = [];
            $Fields = '';

            $fields = $client->describeSObjects(array($table));
            $var = $fields[0]->getFields()->toArray();

            foreach ($var as $key => $value) {
              $sfFields[] = $value->getName();
            }

            foreach ($sfFields as $fieldKey => $fieldValue) {
              $Fields .= $fieldValue . ', ';
            }
            $Fields = substr(trim($Fields), 0, -1);

            $results = $client->query("SELECT " . $Fields . " FROM " . $table . " WHERE " . $field . " in (" . $ID . ")");
              foreach ($results as $key => $row) {
                foreach ($sfFields as $fieldKey => $fieldValue) {
                  if (!empty($row->$fieldValue)) {
                    if (is_string($row->$fieldValue)) {
                      $return[$table][] = $fieldValue . ': ' . $row->$fieldValue;
                    }
                  }
                }
              }
            }
          } catch (Exception $e) {
            print $e;
          }
        }


    dpm($return);

    $element = array(
      '#markup' => 'CNC/Ex Products',
    );

    return $element;
  }

}


