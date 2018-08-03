<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
/**
 * WvUser Model
 *
 * @property |\Cake\ORM\Association\BelongsTo $WvDepartments
 * @property |\Cake\ORM\Association\BelongsTo $WvCountries
 * @property |\Cake\ORM\Association\BelongsTo $WvStates
 * @property |\Cake\ORM\Association\BelongsTo $WvCities
 *
 * @method \App\Model\Entity\WvUser get($primaryKey, $options = [])
 * @method \App\Model\Entity\WvUser newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\WvUser[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\WvUser|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\WvUser patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\WvUser[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\WvUser findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class WvUserTable extends Table
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

        $this->setTable('wv_user');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('HashId', [ 'field' => array( 'default_location_id', 'department_id', 'country_id', 'state_id', 'city_id' ) ]);

        $this->belongsTo('WvAccessRoles', [
            'foreignKey' => 'access_role_ids',
            'joinType' => 'INNER'
        ]);
        $this->belongsTo('WvDepartments', [
            'foreignKey' => 'department_id',
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
        $this->belongsTo('WvFavLocation', [
            'foreignKey' => 'default_location_id',
            'joinType' => 'INNER'
        ]);
        $this->hasOne('WvLoginRecord');
        $this->hasOne('WvEmailVerification');
        $this->hasOne('WvPost');
        $this->hasMany('WvUserFollowers');
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
            ->scalar('firstname')
            ->maxLength('firstname', 256)
            ->requirePresence('firstname', 'create')
            ->notEmpty('firstname');

        $validator
            ->scalar('lastname')
            ->maxLength('lastname', 256)
            ->requirePresence('lastname', 'create')
            ->notEmpty('lastname');

        $validator
            ->scalar('gender')
            ->maxLength('gender', 20)
            ->allowEmpty('gender');

        $validator
            ->email('email')
            ->requirePresence('email', 'create')
            ->notEmpty('email')
            ->add('email', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('password')
            ->maxLength('password', 256)
            ->requirePresence('password', 'create')
            ->notEmpty('password');

        $validator
            ->scalar('phone')
            ->maxLength('phone', 256)
            ->allowEmpty('phone');

        $validator
            ->scalar('address')
            ->maxLength('address', 256)
            ->allowEmpty('address');

        $validator
            ->scalar('latitude')
            ->maxLength('latitude', 256)
            ->allowEmpty('latitude');

        $validator
            ->scalar('longitude')
            ->maxLength('longitude', 256)
            ->allowEmpty('longitude');

        $validator
            ->scalar('profilepic')
            ->maxLength('profilepic', 256)
            ->allowEmpty('profilepic');

        $validator
            ->boolean('status')
            // ->requirePresence('status', 'create')
            ->notEmpty('status');

        $validator
            ->boolean('active')
            // ->requirePresence('active', 'create')
            ->notEmpty('active');

        $validator
            ->boolean('email_verified')
            // ->requirePresence('email_verified', 'create')
            ->notEmpty('email_verified');

        $validator
            ->integer('adhar_verified')
            // ->requirePresence('adhar_verified', 'create')
            ->notEmpty('adhar_verified');

        $validator
            // ->requirePresence('authority_flag', 'create')
            ->notEmpty('authority_flag');

        $validator
            ->scalar('access_role_ids')
            ->maxLength('access_role_ids', 1024)
            // ->requirePresence('access_role_ids', 'create')
            ->notEmpty('access_role_ids');

        $validator
            ->scalar('rwa_name')
            ->maxLength('rwa_name', 1024)
            ->allowEmpty('rwa_name');

        $validator
            ->scalar('designation')
            ->maxLength('designation', 512)
            // ->requirePresence('designation', 'create')
            ->notEmpty('designation');

        $validator
            ->scalar('certificate')
            ->maxLength('certificate', 512)
            // ->requirePresence('certificate', 'create')
            ->notEmpty('certificate');

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
        $rules->add($rules->isUnique(['email']));
        $rules->add($rules->existsIn(['department_id'], 'WvDepartments'));
        $rules->add($rules->existsIn(['country_id'], 'WvCountries'));
        $rules->add($rules->existsIn(['state_id'], 'WvStates'));
        $rules->add($rules->existsIn(['city_id'], 'WvCities'));

        return $rules;
    }

    public function add( $userData = array() ){
      $response = false;
      if( !empty( $userData ) ){
        if( isset( $userData['password'] ) ){
          $users = TableRegistry::get('WvUser');
          $entity = $users->newEntity();
          $entity = $users->patchEntity( $entity, $userData );
          $record = $users->save( $entity );
          if( isset( $record->id ) ){
            $response = $record->id;
          }
        }
      }
      return $response;
    }

    public function checkPassword( $password, $storedPassword ){
      $users = TableRegistry::get('WvUser');
      $entity = $users->newEntity();
      $response = $entity->_checkPassword( $password, $storedPassword );
      return $response;
    }

    public function getUserInfo( $userIds = array() ){
      $response = array();
      if( !empty( $userIds ) ){
        $users = $this->find()->where([ 'id IN' => $userIds, 'status' => 1 ])->toArray();
        foreach( $users as $index => $user ){
          $tmpResponse = $user;
          if( isset( $user['firstname'] ) && isset( $user['lastname'] ) ){
            $tmpResponse['name'] = $user['firstname'].' '.$user['lastname'];
            unset( $tmpResponse['firstname'] );
            unset( $tmpResponse['lastname'] );
          }
          $tmpResponse[ 'profilepic' ] = ( !isset( $tmpResponse[ 'profilepic' ] ) or $tmpResponse[ 'profilepic' ] == null or $tmpResponse[ 'profilepic' ] == '' ) ? 'webroot' . DS . 'img' . DS . 'assets' . DS . 'profile-pic.png' : $tmpResponse[ 'profilepic' ];
          if( isset( $user['access_role_ids'] ) ){
            $accessRoles = $this->WvAccessRoles->getAccessData( json_decode( $user['access_role_ids'] ) );
            $tmpResponse[ 'accessRoles' ] = $accessRoles;
            unset( $tmpResponse['access_role_ids'] );
          }
          $tmpResponse['postCount'] = $this->WvPost->getUserPostCount( $user['id'] );
          $tmpResponse['draftCount'] = $this->WvPost->getUserPostCount( $user['id'], array( 'poststatus' => 0 ) );
          $tmpResponse['bookmarkCount'] = $this->WvPost->WvActivitylog->getBookMarkCount( $user['id'] );
          $tmpResponse['followingCount'] = $this->WvUserFollowers->getfollowingCount( $user['id'] );
          $tmpResponse['followerCount'] = $this->WvUserFollowers->getfollowerCount( $user['id'] );
          $response[ $user->id ] = $tmpResponse;
        }
      }
      return $response;
    }

    public function getUserList( $userIds = array(), $userKeys = array( 'id', 'profilepic', 'firstname', 'lastname' ) ){
      $response = array();
      if( !empty( $userIds ) ){
        $users = $this->find('all')->select( $userKeys )->where([ 'id IN' => $userIds, 'status' => 1 ])->toArray();
        foreach( $users as $index => $user ){
          $tmpResponse = array();
          if( isset( $user['firstname'] ) && isset( $user['lastname'] ) ){
            $tmpResponse['name'] = $user['firstname'].' '.$user['lastname'];
          }
          foreach( $userKeys as $stringKey ){
            if( $stringKey == 'profilepic' )
              $tmpResponse[ 'profilepic' ] = ( $user[ 'profilepic' ] == null or $user[ 'profilepic' ] == '' ) ? 'webroot' . DS . 'img' . DS . 'assets' . DS . 'profile-pic.png' : $user[ 'profilepic' ];
            else
              $tmpResponse[ $stringKey ] = $user[ $stringKey ];
          }
          $response[ $user->id ] = $tmpResponse;
        }
      }
      return $response;
    }

    public function updateUser( $userDatas ){
      $response = array();
      if( !empty( $userDatas ) ){
        $users = TableRegistry::get('WvUser');
        foreach( $userDatas as $user ){
          $entity = $users->get( $user['id'] );
          foreach( $user as $key => $value ){
            if( $key != 'id' ){
              $entity->{$key} = $value;
            }
          }
          $entity = $this->fixEncodings( $entity );
          if( $users->save( $entity ) ){
            $response[] = $user['id'];
          }
        }
      }
      return $response;
    }

    public function checkEmailExist( $email = null ){
      $return = false;
      if( $email != null ){
        $user = TableRegistry::get('WvUser');
        $return = $user->exists( [ 'email' => $email ] );
        return $return;
      }
      return $return;
    }
}
