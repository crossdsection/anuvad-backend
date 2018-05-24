<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * WvLoginRecord Entity
 *
 * @property int $id
 * @property int $user_id
 * @property string $latitude
 * @property string $longitude
 *
 * @property \App\Model\Entity\WvUser $wv_user
 */
class WvLoginRecord extends Entity
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
        'latitude' => true,
        'longitude' => true,
        'wv_user' => true
    ];
}