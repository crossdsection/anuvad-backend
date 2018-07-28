<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;
/**
 * WvPost Controller
 *
 * @property \App\Model\Table\WvPostTable $WvPost
 *
 * @method \App\Model\Entity\WvPost[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class WvPostController extends AppController
{
    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add() {
      $response = array( 'error' => 0, 'message' => '', 'data' => array() );
      if ( $this->request->is('post') ) {
        $postData = $this->request->input('json_decode', true);
        if( !empty( $postData ) ){
          $postData['userId'] = $_POST['userId'];
        } else {
          $postData = $this->request->getData();
        }
        $saveData = array(); $continue = false;
        if( isset( $postData['level'] ) ){
          switch( $postData['level'] ){
            case 'locality' :
              $localeRes = $this->WvPost->WvLocalities->findLocality( $postData )['data'];
              if( !empty( $localeRes['localities'] ) ){
                $saveData['locality_id'] = $localeRes['localities'][0]['locality_id'];
                $continue = true;
              }
              break;
            case 'city' :
              $cityRes = $this->WvPost->WvCities->findCities( $postData )['data'];
              if( !empty( $cityRes['cities'] ) ){
                $saveData['city_id'] = $cityRes['cities'][0]['city_id'];
                $continue = true;
              }
              break;
            case 'state' :
              $stateRes = $this->WvPost->WvStates->findStates( $postData )['data'];
              if( !empty( $stateRes['state'] ) ){
                $saveData['state_id'] = $stateRes['state'][0]['state_id'];
                $continue = true;
              }
              break;
            case 'country' :
              $countryRes = $this->WvPost->WvCountries->findCountry( $postData )['data'];
              if( !empty( $countryRes['countries'] ) ){
                $saveData['country_id'] = $countryRes['countries'][0]['country_id'];
                $continue = true;
              }
              break;
            case 'department' :
              if( isset( $postData['department_id'] ) && $postData['department_id'] != null ){
                $saveData['department_id'] = $postData['department_id'];
                $continue = true;
              }
              break;
          }
        }
        if( isset( $postData[ 'title' ] ) && !empty( $postData[ 'title' ] ) ){
          $saveData[ 'title' ] = $postData[ 'title' ];
        } else {
          $continue = false;
        }
        if( isset( $postData[ 'userId' ] ) && !empty( $postData[ 'userId' ] ) ){
          $saveData[ 'user_id' ] = $postData[ 'userId' ];
          $tmp = $this->WvPost->WvUser->WvLoginRecord->getLastLogin( $postData[ 'userId' ] );
          $saveData[ 'latitude' ] = $tmp[ 'latitude' ];
          $saveData[ 'longitude' ] = $tmp[ 'longitude' ];
        } else {
          $continue = false;
        }
        if( isset( $postData[ 'details' ] ) && !empty( $postData[ 'details' ] ) ){
          $saveData[ 'details' ] = $postData[ 'details' ];
        }
        if( isset( $postData[ 'postType' ] ) && !empty( $postData[ 'postType' ] ) ){
          $saveData[ 'post_type' ] = $postData[ 'postType' ];
          if( $postData[ 'postType' ] == 'court' && !isset( $postData['polls'] ) ){
            $continue = false;
          }
        }
        if( !empty( $postData[ 'filejson' ] ) ){
          $saveData[ 'filejson' ] = json_encode( $postData[ 'filejson' ] );
        }
        if ( $continue ){
          $returnId = $this->WvPost->savePost( $saveData );
          if ( $returnId ) {
            if( $saveData[ 'post_type' ] == 'court' ){
              $data = array( 'post_id' => $returnId, 'polls' => $postData['polls'] );
              $return = $this->WvPost->WvPolls->savePolls( $data );
            }
            $response = array( 'error' => 0, 'message' => 'Post Submitted', 'data' => array() );
          }
        } else {
          $response = array( 'error' => 1, 'message' => 'Error', 'data' => array() );
        }
      }
      $this->response = $this->response->withType('application/json')
                                       ->withStringBody( json_encode( $response ) );
      return $this->response;
    }

    /**
     * Feed method
     *
     * @param string|null $id Wv Post id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function getpost( $id = null )
    {
      $id = $this->request->getParam('id');
      $response = array( 'error' => 0, 'message' => '', 'data' => array() );
      $wvPost = $this->WvPost->find('all')->where(['id' => $id]);
      if( !empty( $wvPost ) ){
        $response['data'] = $this->WvPost->retrievePostDetailed( $wvPost );
      } else {
        $response = array( 'error' => 0, 'message' => 'Invalid Param', 'data' => array() );
      }
      $this->response = $this->response->withType('application/json')
                                       ->withStringBody( json_encode( $response ) );
      return $this->response;
    }

    /**
     * GetFeed method
     *
     * @param string|null $id Wv Post id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function getfeed($id = null)
    {
      $response = array( 'error' => 0, 'message' => '', 'data' => array() );
      if( isset( $_GET['page'] ) ){
        $query = $this->WvPost->find('all'); $orderBy = array();
        $wvPost = $query->page( $_GET['page'] );
        if( isset( $_GET['count'] ) ){
          $wvPost = $query->limit( $_GET['count'] );
        } else {
          $wvPost = $query->limit( 10 );
        }
        if( isset( $_GET['posttype'] ) ){
          $wvPost = $query->where( [ 'post_type' => $_GET['posttype'] ] );
        }
        if( isset( $_GET['most_upvoted'] ) && $_GET['most_upvoted'] == 1 ){
          $orderBy[] = 'total_upvotes DESC';
        }
        if( isset( $_GET['sort_datetime'] ) && $_GET['sort_datetime'] == 1 ){
          $orderBy[] = 'created ASC';
        } else {
          $orderBy[] = 'created DESC';
        }
        $wvPost = $query->order( $orderBy );
        if( !empty( $wvPost ) ){
          $response['data'] = $this->WvPost->retrievePostDetailed( $wvPost );
        } else {
          $response = array( 'error' => 0, 'message' => 'Your Feed is Empty.', 'data' => array() );
        }
      } else {
        $response = array( 'error' => 1, 'message' => 'Invalid Request.', 'data' => array() );
      }
      $this->response = $this->response->withType('application/json')
                                       ->withStringBody( json_encode( $response ) );
      return $this->response;
    }
}
