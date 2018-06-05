<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * WvOauth Entity
 *
 * @property int $id
 * @property int $user_id
 * @property string $provider_id
 * @property string $access_token
 * @property \Cake\I18n\FrozenTime $expiration_time
 * @property \Cake\I18n\FrozenTime $created
 * @property \Cake\I18n\FrozenTime $modified
 *
 * @property \App\Model\Entity\User $user
 * @property \App\Model\Entity\Provider $provider
 */
class WvOauth extends Entity
{

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        'user_id' => true,
        'provider_id' => true,
        'access_token' => true,
        'issued_at' => true,
        'expiration_time' => true,
        'created' => true,
        'modified' => true,
        'id' => false,
    ];


}