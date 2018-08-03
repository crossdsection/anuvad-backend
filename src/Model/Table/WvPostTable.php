<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Utility\Hash;

/**
 * WvPost Model
 *
 * @property |\Cake\ORM\Association\BelongsTo $Departments
 * @property |\Cake\ORM\Association\BelongsTo $Users
 * @property |\Cake\ORM\Association\BelongsTo $Countries
 * @property |\Cake\ORM\Association\BelongsTo $States
 * @property |\Cake\ORM\Association\BelongsTo $Cities
 * @property |\Cake\ORM\Association\BelongsTo $Localities
 *
 * @method \App\Model\Entity\WvPost get($primaryKey, $options = [])
 * @method \App\Model\Entity\WvPost newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\WvPost[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\WvPost|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\WvPost|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\WvPost patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\WvPost[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\WvPost findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class WvPostTable extends Table
{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('wv_post');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('ArrayOps');
        $this->addBehavior('HashId', ['field' => array( 'user_id', 'city_id', 'department_id', 'locality_id', 'country_id', 'state_id', 'locality_id' ) ]);

        $this->belongsTo('WvDepartments', [
            'foreignKey' => 'department_id',
            'joinType' => 'INNER'
        ]);
        $this->belongsTo('WvUser', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER'
        ]);
        $this->belongsTo('WvCountries', [
            'foreignKey' => 'country_id',
            'joinType' => 'INNER'
        ]);
        $this->belongsTo('WvStates', [
            'foreignKey' => 'state_id',
            'joinType' => 'INNER'
        ]);
        $this->belongsTo('WvCities', [
            'foreignKey' => 'city_id',
            'joinType' => 'INNER'
        ]);
        $this->belongsTo('WvLocalities', [
            'foreignKey' => 'locality_id',
            'joinType' => 'INNER'
        ]);
        $this->hasOne('WvActivitylog');
        $this->hasOne('WvPolls');
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->integer('id')
            ->allowEmpty('id', 'create');

        $validator
            ->integer('user_id')
            ->notEmpty('user_id');

        $validator
            ->integer('total_upvotes');

        $validator
            ->integer('total_score');

        $validator
            ->scalar('title')
            ->maxLength('title', 100)
            ->requirePresence('title', 'create')
            ->notEmpty('title');

        $validator
            ->scalar('details')
            ->maxLength('details', 512)
            ->requirePresence('details', 'create')
            ->notEmpty('details');

        $validator
            ->scalar('filejson')
            ->maxLength('filejson', 512)
            ->requirePresence('filejson', 'create')
            ->notEmpty('filejson');

        $validator
            ->boolean('poststatus')
            ->notEmpty('poststatus');

        $validator
            ->scalar('latitude')
            ->maxLength('latitude', 100)
            ->requirePresence('latitude', 'create')
            ->notEmpty('latitude');

        $validator
            ->scalar('longitude')
            ->maxLength('longitude', 100)
            ->requirePresence('longitude', 'create')
            ->notEmpty('longitude');

        $validator
            ->scalar('post_type')
            ->requirePresence('post_type', 'create')
            ->notEmpty('post_type');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
        // $rules->add($rules->existsIn(['department_id'], 'WvDepartments'));
        // $rules->add($rules->existsIn(['user_id'], 'WvUser'));
        // $rules->add($rules->existsIn(['country_id'], 'WvCountries'));
        // $rules->add($rules->existsIn(['state_id'], 'WvStates'));
        // $rules->add($rules->existsIn(['city_id'], 'WvCities'));
        // $rules->add($rules->existsIn(['locality_id'], 'WvLocalities'));
        return $rules;
    }

    public function savePost( $postData = array() ){
      $return = false;
      if( !empty( $postData ) ){
        $post = TableRegistry::get('WvPost');
        $entity = $post->newEntity();
        $entity = $post->patchEntity( $entity, $postData );
        $entity = $this->fixEncodings( $entity );
        $record = $post->save( $entity );
        if( isset( $record->id ) ){
          $return = $record->id;
        }
      }
      return $return;
    }

    public function retrievePostDetailed( $wvPost ){
      $fileuploadIds = array(); $userIds = array(); $postIds = array();
      $localityIds = array(); $localityCityMap = array();
      $data = array();
      if( !empty( $wvPost ) ){
        $accessRoleIds = array();
        if( isset( $_POST['accessRoleIds'] ) )
          $accessRoleIds = $_POST['accessRoleIds'];
        $locationTag = array( 'city_id' => array(), 'state_id' => array(), 'country_id' => array());
        foreach ( $wvPost as $key => $value ) {
          $fileuploadIds = array_merge( $fileuploadIds, json_decode( $value['filejson'] ) );
          $userIds[] = $value->user_id;
          $postIds[] = $value->id;
          if( $value->locality_id != 0 )
            $localityIds[] = $value->locality_id;
          if( $value->city_id != 0 )
            $locationTag['city_id'][] = $value->city_id;
          if( $value->state_id != 0 )
            $locationTag['state_id'][] = $value->state_id;
          if( $value->country_id != 0 )
            $locationTag['country_id'][] = $value->country_id;
        }
        $this->WvFileuploads = TableRegistry::get('WvFileuploads');
        $fileResponse = $this->WvFileuploads->getfileurls( $fileuploadIds );
        $userInfos = $this->WvUser->getUserList( $userIds );
        $postProperties = $this->WvActivitylog->getCumulativeResult( $postIds );
        $postPolls = $this->WvPolls->getPolls( $postIds );
        if( !empty( $localityIds ) ){
          $localityRes = $this->WvLocalities->findLocalityById( $localityIds );
          if( !empty( $localityRes['data']['cities'] )){
            $localityCityMap = Hash::combine( $localityRes['data']['localities'], '{n}.locality_id', '{n}.city_id' );
            $cityIds = Hash::extract( $localityRes['data']['cities'], '{n}.city_id' );
            $locationTag['city_id'] = array_merge( $cityIds, $locationTag['city_id'] );
          }
        }
        if( !empty( $locationTag['city_id'] ) || !empty( $locationTag['state_id'] ) || !empty( $locationTag['country_id'] ) ){
          $locationTag['city_id'] = array_unique( $locationTag['city_id'] );
          $locationTag['state_id'] = array_unique( $locationTag['state_id'] );
          $locationTag['country_id'] = array_unique( $locationTag['country_id'] );
          $accessData = $this->WvUser->WvAccessRoles->retrieveAccessRoleIds( $locationTag );
          $accessData = $this->array_group_by( $accessData, 'area_level', 'area_level_id');
        }
        foreach ( $wvPost as $key => $value ) {
          if( $value['user_id'] == null ){
            continue;
          }
          $accessRoleId = 0; $accessRoleArr = array();
          if( $value->locality_id != 0 ){
            $cityId = $localityCityMap[ $value->locality_id ];
            $accessRoleArr = $accessData['city'][ $cityId ];
          } else if( $value->city_id != 0 ){
            $accessRoleArr = $accessData['city'][ $value->city_id ];
          } else if( $value->state_id != 0 ){
            $accessRoleArr = $accessData['state'][ $value->state_id ];
          } else if( $value->country_id != 0 ){
            $accessRoleArr = $accessData['country'][ $value->country_id ];
          }
          $permission = array( 'enable' => 0, 'authority' => 0 );
          foreach( $accessRoleArr as $accessRole ){
            if( $accessRole['id'] != 0 && in_array( $accessRole['id'], $accessRoleIds ) ){
              if( $accessRole['access_level'] >= 1 )
                $permission['enable'] = 1;
              if( $accessRole['access_level'] == 2 ){
                $permission['authority'] = 1;
                break;
              }
            }
          }
          if( !empty( $fileResponse['data']  ) ){
            $fileJSON = json_decode( $value->filejson );
            $value['files'] = array();
            foreach( $fileJSON as $key => $id ){
              if( isset( $fileResponse['data'][ $id ] ) ){
                $value['files'][] = $fileResponse['data'][ $id ];
              }
            }
          }
          $value['props'] = array(); $value['polls'] = array();
          if( isset( $postProperties[ $value['id'] ] ) ){
            $value['props'] = $postProperties[ $value['id'] ];
          }
          if( isset( $postPolls[ $value['id'] ] ) ){
            $value['polls'] = $postPolls[ $value['id'] ];
          }
          $value['permissions'] = $permission;
          unset( $value['filejson'] );
          $value['user'] = $userInfos[ $value['user_id'] ];
          unset( $value['user_id'] );
          $data[] = $value;
        }
      }
      return $data;
    }

    public function changeUpvotes( $postId = null, $change = null ){
      $response = false;
      if( $postId != null && $change != null ){
        $post = TableRegistry::get('WvPost');
        $entity = $post->get( $postId );
        $entity->total_upvotes = $entity->total_upvotes + $change;
        if( $post->save( $entity ) ){
          $response = true;
        }
      }
      return $response;
    }

    public function getUserPostCount( $userId = null, $queryConditions = array() ){
      $response = null;
      if( $userId != null ){
        $post = TableRegistry::get('WvPost');
        $conditions = array( 'user_id' => $userId );
        $query = $post->find();
        if( !empty( $queryConditions ) ){
          if( $queryConditions['poststatus'] == 0 ){
            $conditions[] = array( 'poststatus' => 0 );
          } else {
            $conditions[] = array( 'poststatus' => 1 );
          }
        }
        $query = $query->where( $conditions );
        $totalPosts = $query->count();
        $response = $totalPosts;
      }
      return $response;
    }
}
