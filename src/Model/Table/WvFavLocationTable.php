<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Utility\Hash;

/**
 * WvFavLocation Model
 *
 * @property |\Cake\ORM\Association\BelongsTo $User
 * @property |\Cake\ORM\Association\BelongsTo $Departments
 * @property |\Cake\ORM\Association\BelongsTo $Countries
 * @property |\Cake\ORM\Association\BelongsTo $States
 * @property |\Cake\ORM\Association\BelongsTo $Cities
 * @property |\Cake\ORM\Association\BelongsTo $Localities
 *
 * @method \App\Model\Entity\WvFavLocation get($primaryKey, $options = [])
 * @method \App\Model\Entity\WvFavLocation newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\WvFavLocation[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\WvFavLocation|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\WvFavLocation|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\WvFavLocation patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\WvFavLocation[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\WvFavLocation findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class WvFavLocationTable extends Table
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

        $this->setTable('wv_fav_location');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('WvUser', [
            'foreignKey' => 'user_id',
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
        $this->belongsTo('WvLocalities', [
            'foreignKey' => 'locality_id',
            'joinType' => 'INNER'
        ]);
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
        $rules->add($rules->existsIn(['user_id'], 'WvUser'));
        $rules->add($rules->existsIn(['department_id'], 'WvDepartments'));
        $rules->add($rules->existsIn(['country_id'], 'WvCountries'));
        $rules->add($rules->existsIn(['state_id'], 'WvStates'));
        $rules->add($rules->existsIn(['city_id'], 'WvCities'));
        $rules->add($rules->existsIn(['locality_id'], 'WvLocalities'));

        return $rules;
    }

    public function add( $postData ){
      $return = false;
      if( !empty( $postData ) ){
        $favLocal = TableRegistry::get('WvFavLocation');
        $entity = $favLocal->newEntity();
        $entity = $favLocal->patchEntity( $entity, $postData );
        $record = $favLocal->save( $entity );
        return $record;
      }
      return $return;
    }

    public function traverseAndMatch( $data, $key, $value ){
      $return = array();
      foreach ( $data as $index => $singleton ) {
        if( isset( $singleton[ $key ] ) && $singleton[ $key ] == $value ){
          $return = $singleton;
          break;
        }
      }
      return $return;
    }

    public function buildDataForSearch( $wvFavLocations ){
      $search = array( 'localityIds' => array(), 'cityIds' => array(), 'stateIds' => array(), 'countryIds' => array() );
      if( !empty( $wvFavLocations ) ){
        foreach ( $wvFavLocations as $key => $favLoc ) {
          if( $favLoc->level == 'locality' ){
            $search['localityIds'][ $favLoc->locality_id ] = array( 'locality_id' => $favLoc->locality_id, 'latitude' => $favLoc->latitude, 'longitude' => $favLoc->longitude, 'level' => $favLoc->level );
          } else if( $favLoc->level == 'city' ){
            $search['cityIds'][ $favLoc->city_id ] = array( 'city_id' => $favLoc->city_id, 'latitude' => $favLoc->latitude, 'longitude' => $favLoc->longitude, 'level' => $favLoc->level );
          } else if( $favLoc->level == 'state' ){
            $search['stateIds'][ $favLoc->state_id ] = array( 'state_id' => $favLoc->state_id, 'latitude' => $favLoc->latitude, 'longitude' => $favLoc->longitude, 'level' => $favLoc->level );
          } else if( $favLoc->level == 'country' ){
            $search['countryIds'][ $favLoc->country_id ] = array( 'country_id' => $favLoc->country_id, 'latitude' => $favLoc->latitude, 'longitude' => $favLoc->longitude, 'level' => $favLoc->level );
          }
        }
      }
      return $search;
    }
    public function retrieveAddresses( $search ){
      $response = array( 'error' => 0, 'message' => '', 'data' => array() );
      if( !empty( $search ) ){
        $data = array();
        if( !empty( $search['localityIds'] ) ){
          $localityIds = Hash::extract( $search['localityIds'], '{n}.locality_id' );
          $localityRes = $this->WvLocalities->findLocalityById( $localityIds );
          if( $localityRes['error'] == 0 ){
            foreach ( $localityRes['data']['localities'] as $key => $locale ) {
              $localityId = $locale['locality_id'];
              $city = $this->traverseAndMatch( $localityRes['data']['cities'], 'city_id', $locale['city_id'] );
              $state = $this->traverseAndMatch( $localityRes['data']['states'], 'state_id', $city['state_id'] );
              $country = $this->traverseAndMatch( $localityRes['data']['countries'], 'country_id', $state['country_id'] );
              $address = $locale['locality_name'].', '.$city['city_name'].', '.$state['state_name'].', '.$country['country_name'];
              $data[] = array( 'address_string' => $address, 'latitude' => $search['localityIds'][ $localityId ]['latitude'], 'longitude' => $search['localityIds'][ $localityId ]['longitude'], 'level' => $search['localityIds'][ $localityId ]['level'] );
            }
          }
        }
        if( !empty( $search['cityIds'] ) ){
          $cityIds = Hash::extract( $search['cityIds'], '{n}.city_id' );
          $cityRes = $this->WvCities->findCitiesById( $cityIds );
          if( $cityRes['error'] == 0 ){
            foreach ( $cityRes['data']['cities'] as $key => $city ) {
              $cityId = $city['city_id'];
              $state = $this->traverseAndMatch( $cityRes['data']['states'], 'state_id', $city['state_id'] );
              $country = $this->traverseAndMatch( $cityRes['data']['countries'], 'country_id', $state['country_id'] );
              $address = $city['city_name'].', '.$state['state_name'].', '.$country['country_name'];
              $data[] = array( 'address_string' => $address, 'latitude' => $search['cityIds'][ $cityId ]['latitude'], 'longitude' => $search['cityIds'][ $cityId ]['longitude'], 'level' => $search['cityIds'][ $cityId ]['level'] );
            }
          }
        }
        if( !empty( $search['stateIds'] ) ){
          $stateIds = Hash::extract( $search['stateIds'], '{n}.state_id' );
          $stateRes = $this->WvStates->findStateById( $stateIds );
          if( $stateRes['error'] == 0 ){
            foreach ( $stateRes['data']['states'] as $key => $state ) {
              $stateId = $state['state_id'];
              $country = $this->traverseAndMatch( $stateRes['data']['countries'], 'country_id', $state['country_id'] );
              $address = $state['state_name'].', '.$country['country_name'];
              $data[] = array( 'address_string' => $address, 'latitude' => $search['stateIds'][ $stateId ]['latitude'], 'longitude' => $search['stateIds'][ $stateId ]['longitude'], 'level' => $search['stateIds'][ $stateId ]['level'] );
            }
          }
        }
        if( !empty( $search['countryIds'] ) ){
          $countryIds = Hash::extract( $search['countryIds'], '{n}.country_id' );
          $countryRes = $this->WvCountries->findCountryById( $countryIds );
          if( $countryRes['error'] == 0 ){
            foreach ( $countryRes['data']['countries'] as $key => $country ) {
              $countryId = $country['country_id'];
              $address = $country['country_name'];
              $data[] = array( 'address_string' => $address, 'latitude' => $search['countryIds'][ $countryId ]['latitude'], 'longitude' => $search['countryIds'][ $countryId ]['longitude'], 'level' => $search['countryIds'][ $countryId ]['level'] );
            }
          }
        }
        $response['data'] = $data;
      }
      return $response;
    }

    public function remove( $favLocalId ){
      $ret = false;
      if( !empty( $favLocalId ) ){
        $favLoc = $this->find()->where([ 'id IN' => $favLocalId ]);
        if( !empty( $favLoc ) ){
          $ret = $this->deleteAll( [ 'id IN' => $favLocalId ] );
        }
      }
      return $ret;
    }
}
