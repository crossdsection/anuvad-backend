<?php
namespace App\Controller;

use App\Controller\AppController;

/**
 * WvUser Controller
 *
 * @property \App\Model\Table\WvUserTable $WvUser
 *
 * @method \App\Model\Entity\WvUser[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class WvUserController extends AppController {

    public $components = array('OAuth');

    /**
     * Signup API
     */
    public function signup() {
        $response = array( 'error' => 1, 'message' => '', 'data' => array() );
        $userData = array();
        $postKeys = array('email', 'password','firstName','lastName','latitude','longitude','gender','phone','certificate');
        $postData = $this->request->input('json_decode', true);
        if( empty( $postData ) ){
          $postData = $this->request->data;
        }
        if ( !empty( $postData ) ){
          foreach( $postKeys as $postKey ){
            if( isset( $postData[ $postKey ] ) && !empty( $postData[ $postKey ] ) ){
              $newKey = strtolower( $postKey );
              if( $postKey == 'certificate' ){
                $file = $postData[ $postKey ];
                $filePath = 'img' . DS . 'upload' . DS . $file['name'];
                $fileUrl = WWW_ROOT . $filePath;
                if( move_uploaded_file( $file['tmp_name'], $fileUrl ) ){
                  $userData[ $newKey ] = $filePath;
                }
              } else {
                $userData[ $newKey ] = $postData[ $postKey ];
              }
            }
          }
          $localityCheck = false;
          $localityCheckArray = array( 'locality', 'city', 'latitude', 'longitude' );
          $localityData = array();
          foreach ( $localityCheckArray as $key => $value ) {
            if( isset( $postData[ $value ] ) && ( $postData[ $value ] != '' or $postData[ $value ] != 0 ) ){
              $localityData[ $value ] = $postData[ $value ];
              $localityCheck = true;
            } else {
              $localityCheck = false;
              break;
            }
          }
          if( $localityCheck ){
            $response = $this->WvUser->WvCities->WvLocalities->findLocality( $localityData );
            if( $response['error'] == 0 ){
              $userData['city_id'] = $response['data']['city_id'];
              $userData['state_id'] = $response['data']['city_id'];
              $userData['country_id'] = $response['data']['country_id'];
            }
          }
          if( !empty( $userData ) && $this->WvUser->add( $userData ) ){
            $response = array( 'error' => 0, 'message' => 'Registration Successful', 'data' => array() );
          } else {
            $response = array( 'error' => 1, 'message' => 'Registration Failed', 'data' => array() );
          }
        } else {
          $response = array( 'error' => 1, 'message' => 'Registration Failed', 'data' => array() );
        }
        $this->response = $this->response->withType('application/json')
                                         ->withStringBody( json_encode( $response ) );
        return $this->response;
    }

    /**
     * Login API
     */
    public function login() {
        $response = array( 'error' => 0, 'message' => '', 'data' => array() );
        $postData = $this->request->input('json_decode', true);
        if( empty( $postData ) ){
          $postData = $this->request->data;
        }
        if( isset( $postData['username'] ) && isset( $postData['password'] ) ){
          $user = $this->WvUser->find()->where([ 'email' => $postData['username'] ])->toArray();
          if( !empty( $user ) && $this->WvUser->checkPassword( $user[0]->password, $postData['password'] ) ){
            $res = $this->OAuth->getAccessToken( $user[0]->id );
            if( $res['error'] == 0 ){
               $latitude = 0;
               $longitude = 0;
               if( isset( $postData['latitude'] ) && $postData['latitude'] != 0 ){
                 $latitude = $postData['latitude'];
               }
               if( isset( $postData['longitude'] ) && $postData['latitude'] != 0 ){
                 $longitude = $postData['longitude'];
               }
               $userData = array(
                 'user_id'  => $user[0]->id,
                 'latitude' => $latitude,
                 'longitude'=> $longitude
               );
               $ret = $this->WvUser->WvLoginRecord->saveLog( $userData );
               $localityCheck = false;
               $localityCheckArray = array( 'locality', 'city', 'latitude', 'longitude' );
               $localityData = array();
               foreach ( $localityCheckArray as $key => $value ) {
                 if( isset( $postData[ $value ] ) && ( $postData[ $value ] != '' or $postData[ $value ] != 0 ) ){
                   $localityData[ $value ] = $postData[ $value ];
                   $localityCheck = true;
                 } else {
                   $localityCheck = false;
                   break;
                 }
               }
               if( $localityCheck ){
                 $response = $this->WvUser->WvCities->WvLocalities->findLocality( $localityData );
                 pr( $response );exit;
                 if( $response['error'] == 0 ){
                   $userData['city_id'] = $response['data']['city_id'];
                   $userData['state_id'] = $response['data']['city_id'];
                   $userData['country_id'] = $response['data']['country_id'];
                 }
               }
            }
            $response = array( 'error' => $res['error'], 'message' => $res['message'], 'data' => $res['data'] );
          } else {
            $response = array( 'error' => 1, 'message' => 'Invalid Login', 'data' => array() );
          }
        } else {
          $response = array( 'error' => 1, 'message' => 'Invalid Login', 'data' => array() );
        }
        $this->response = $this->response->withType('application/json')
                                         ->withStringBody( json_encode( $response ) );
        return $this->response;
    }

    public function getuserinfo(){
        $response = array( 'error' => 0, 'message' => '', 'data' => array() );
        $postData = $this->request->input('json_decode', true);
        if( empty( $postData ) ){
          $postData = $this->request->data;
        }
        if( isset( $postData['userId'] ) && !empty( $postData['userId'] ) ){
          $data = $this->WvUser->getUserInfo( $postData['userId'] );
          $response['data'] = array_values( $data );
        }
        $this->response = $this->response->withType('application/json')
                                         ->withStringBody( json_encode( $response ) );
        return $this->response;
    }
}
