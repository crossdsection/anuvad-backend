<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

/**
 * WvAccessRoles Model
 *
 * @property \App\Model\Table\AreaLevelsTable|\Cake\ORM\Association\BelongsTo $AreaLevels
 *
 * @method \App\Model\Entity\WvAccessRole get($primaryKey, $options = [])
 * @method \App\Model\Entity\WvAccessRole newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\WvAccessRole[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\WvAccessRole|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\WvAccessRole|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\WvAccessRole patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\WvAccessRole[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\WvAccessRole findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class WvAccessRolesTable extends Table
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

        $this->setTable('wv_access_roles');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        //
        // $this->belongsTo('AreaLevels', [
        //     'foreignKey' => 'area_level_id',
        //     'joinType' => 'INNER'
        // ]);
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
            ->scalar('area_level')
            ->requirePresence('area_level', 'create')
            ->notEmpty('area_level');

        $validator
            ->integer('access_level')
            ->requirePresence('access_level', 'create')
            ->notEmpty('access_level');

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
        return $rules;
    }

    public function getAccessData( $roleIds ){
      $data = array();
      if( !empty( $roleIds  ) ){
        $accessRoles = $this->find('all')->where([ 'id IN' => $roleIds ])->toArray();
        $accessLevels = array( '0' => 'r', '1' => 'w', '2' => 'a' );
        $areaWiseModels = array( 'country' => 'WvCountries', 'city'  => 'WvCities', 'province'  => 'WvStates', 'department'  => 'WvDepartment' );
        foreach( $accessRoles as $accessRole ){
          $areaLevel = $accessRole['area_level'];
          $areaModel =  TableRegistry::get( $areaWiseModels[ $areaLevel ] );
          $return = $areaModel->find()->where( [ 'id' => $accessRole['area_level_id'] ] )->toArray();
          $data[] = array( 'area' => $return[0]->name, 'access_level' => $accessLevels[ $accessRole['access_level'] ]);
        }
      }
      return $data;
    }

    /*
     * data[ country_id ]
     * data[ city_id ]
     * data[ state_id ]
     */
    public function retrieveAccessRoleIds( $data, $accessLevel ){
      $response = array();
      if( !empty( $data  ) ){
        $locationKeyMap = array(
          'country_id' => 'country', 'country' => 'country_id',
          'state_id' => 'state', 'state' => 'state_id',
          'city_id' => 'city', 'city' => 'city_id'
        );
        $conditions = array( 'OR' => array() );
        foreach( $data as $key => $ids ){
          $areaLevel = $locationKeyMap[ $key ];
          $conditions['OR'][] = array( 'area_level' => $areaLevel, 'area_level_id IN' => $ids, 'access_level' => $accessLevel );
        }
        $accessRoles = $this->find('all')
                            ->where( $conditions )
                            ->toArray();
        $accessRolesFound = array(); $accessRoleNotFound = array();
        foreach( $accessRoles as $key => $access ){
          $dataKey = $locationKeyMap[ $access['area_level'] ];
          if( in_array( $access['area_level_id'], $data[ $dataKey ] ) ){
            $accessRolesFound[] = array( 'id' => $access['id'], 'area_level' => $access['area_level'],
                                 'area_level_id' => $access['area_level_id'] );
            $data[ $dataKey ] = array_diff( $data[ $dataKey ], array( $access['area_level_id'] ) );
          }
        }
        $returnData = $this->addAccess( $data, $accessLevel );
        $response = array_merge( $accessRolesFound, $returnData );
      }
      return $response;
    }

    /*
     * data[ country_id ]
     * data[ city_id ]
     * data[ state_id ]
     * data[ access_level ]
     */
    public function addAccess( $data, $accessLevel ){
      $response = array();
      if( !empty( $data ) ){
        $accessData = array();
        $locationKeyMap = array(
          'country_id' => 'country', 'country' => 'country_id',
          'state_id' => 'state', 'state' => 'state_id',
          'city_id' => 'city', 'city' => 'city_id'
        );
        foreach( $data as $key => $access ){
          $areaLevel = $locationKeyMap[ $key ];
          foreach( $access as $locationIds ){
            $accessData[] = array( 'area_level' => $areaLevel, 'area_level_id' => $locationIds, 'access_level' => $accessLevel );
          }
        }
        $accessRoles = TableRegistry::get('WvAccessRoles');
        $accessData = $accessRoles->newEntities( $accessData );
        $result = $accessRoles->saveMany( $accessData );
        if( !empty( $result ) ){
          foreach( $result as $data ){
            $response[] = array( 'id' => $data['id'], 'area_level' => $data['area_level'],
                                 'area_level_id' => $data['area_level_id'] );
          }
        }
      }
      return $response;
    }
}
