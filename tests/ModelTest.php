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

namespace WebFu\SimpleRepository\Tests;

use PHPUnit\Framework\TestCase;
use WebFu\SimpleRepository\Model;

/**
 * @coversDefaultClass \WebFu\SimpleRepository\Model
 */
class ModelTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function testModelWithAnnotations(): void {

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

        $this->assertEquals(1, $user->getId());
        $this->assertEquals('John Doe', $user->getName());
        $this->assertEquals('john.doe@none.com', $user->getEmail());
    }

    public function testModelWithAttributes(): void {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped('Attributes are supported since PHP 8.0');
        }

        $userClass = new class extends Model {
            #[\WebFu\SimpleRepository\Column(name: "user_id", nullable: false)]
            protected int $id;
            #[\WebFu\SimpleRepository\Column(name: "name", nullable: false, length: 100)]
            protected string $name;
            #[\WebFu\SimpleRepository\Column(name: "email", nullable: false, length: 150)]
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

        $this->assertEquals(1, $user->getId());
        $this->assertEquals('John Doe', $user->getName());
        $this->assertEquals('john.doe@none.com', $user->getEmail());
    }
}