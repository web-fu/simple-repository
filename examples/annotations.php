<?php
declare(strict_types=1);

/**
 * This file is part of web-fu/simple-repository
 *
 * @copyright Web-Fu <info@web-fu.it>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__.'/../vendor/autoload.php';

use WebFu\SimpleRepository\Model;

$userClass = new class extends Model {
    /**
     * @column(name="id", nullable=false)
     */
    protected int $id;
    /**
     * @column(name="name", nullable=false, length=100)
     */
    protected string $name;
    /**
     * @column(name="email", nullable=false, length=150)
     */
    protected string $email;

    public function getId():int {
        return $this->id;
    }
    public function getName():string {
        return $this->name;
    }
    public function getEmail():string {
        return $this->email;
    }
};


$user = new $userClass([
    'user_id' => 1,
    'name'    => 'John Doe',
    'email'   => 'john.doe@none.com'
]);

var_dump($user);