<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
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
     * Index method
     *
     * @return \Cake\Http\Response|void
     */
    public function index()
    {
        $this->paginate = [
            'contain' => ['WvDepartments', 'WvUser', 'WvCountries', 'WvStates', 'WvCities', 'WvLocalities']
        ];
        $wvPost = $this->paginate($this->WvPost);

        $this->set(compact('wvPost'));
    }

    /**
     * View method
     *
     * @param string|null $id Wv Post id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $wvPost = $this->WvPost->get($id, [
            'contain' => ['WvDepartments', 'WvUser', 'WvCountries', 'WvStates', 'WvCities', 'WvLocalities']
        ]);

        $this->set('wvPost', $wvPost);
    }

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
        $importantKeys = array( 'department_id', 'country_id', 'state_id', 'city_id', 'locality_id' );
        foreach( $importantKeys as $key ){
          if( isset( $postData[ $key ] ) && !empty( $postData[ $key ] ) ){
            $saveData[ $key ] = $postData[ $key ];
            $continue = true;
          } else {
            $saveData[ $key ] = 0;
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
        }
        if( !empty( $postData[ 'filejson' ] ) ){
          $saveData[ 'filejson' ] = json_encode( $postData[ 'filejson' ] );
        }
        if ( $continue ){
          if ( $this->WvPost->savePost( $saveData ) ) {
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
    public function getpost($id = null)
    {
      $id = $this->request->getParam('id');
      $response = array( 'error' => 0, 'message' => '', 'data' => array() );
      $wvPost = $this->WvPost->find('all', ['limit' => 200])->where(['id' => $id]);
      $fileuploadIds = array(); $userIds = array(); $data = array();
      if( !empty( $wvPost ) ){
        foreach ( $wvPost as $key => $value ) {
           $fileuploadIds = array_merge( $fileuploadIds, json_decode( $value['filejson'] ) );
           $userIds[] = $value->user_id;
        }
        $this->WvFileuploads = TableRegistry::get('WvFileuploads');
        $fileResponse = $this->WvFileuploads->getfileurls( $fileuploadIds );
        $userInfos = $this->WvPost->WvUser->getUserInfo( $userIds );
        foreach ( $wvPost as $key => $value ) {
          if( !empty( $fileResponse['data']  ) ){
            $fileJSON = json_decode( $value->filejson );
            $value['files'] = array();
            foreach( $fileJSON as $key => $id ){
              if( isset( $fileResponse['data'][ $id ] ) ){
                $value['files'][] = $fileResponse['data'][ $id ];
              }
            }
          }
          unset( $value['filejson'] );
          $value['user'] = $userInfos[ $value['user_id'] ];
          unset( $value['user_id'] );
          $data[] = $value;
        }
        $response['data'] = $data;
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
      $data = array( 'discussion' => array(), 'court' => array(), 'news' => array() );
      $wvPost = $this->WvPost->find('all', ['limit' => 200]);
      $fileuploadIds = array(); $userIds = array(); $postIds = array();
      if( !empty( $wvPost ) ){
        foreach ( $wvPost as $key => $value ) {
           $fileuploadIds = array_merge( $fileuploadIds, json_decode( $value['filejson'] ) );
           $userIds[] = $value->user_id;
           $postIds[] = $value->id;
        }
        $this->WvFileuploads = TableRegistry::get('WvFileuploads');
        $fileResponse = $this->WvFileuploads->getfileurls( $fileuploadIds );
        $userInfos = $this->WvPost->WvUser->getUserInfo( $userIds );
        $postProperties = $this->WvPost->WvActivitylog->getCumulativeResult( $postIds );
        foreach ( $wvPost as $key => $value ) {
          if( !empty( $fileResponse['data']  ) ){
            $fileJSON = json_decode( $value->filejson );
            $value['files'] = array();
            foreach( $fileJSON as $key => $id ){
              if( isset( $fileResponse['data'][ $id ] ) ){
                $value['files'][] = $fileResponse['data'][ $id ];
              }
            }
          }
          if( isset( $postProperties[ $value['id'] ] ) ){
            $value['props'] = $postProperties[ $value['id'] ];
          }
          unset( $value['filejson'] );
          $value['user'] = $userInfos[ $value['user_id'] ];
          unset( $value['user_id'] );
          $data[ $value->post_type ][] = $value;
        }
        $response['data'] = $data;
      } else {
        $response = array( 'error' => 0, 'message' => 'Your Feed is Empty', 'data' => array() );
      }
      $this->response = $this->response->withType('application/json')
                                       ->withStringBody( json_encode( $response ) );
      return $this->response;
    }

    /**
     * Delete method
     *
     * @param string|null $id Wv Post id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $wvPost = $this->WvPost->get($id);
        if ($this->WvPost->delete($wvPost)) {
            $this->Flash->success(__('The wv post has been deleted.'));
        } else {
            $this->Flash->error(__('The wv post could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
