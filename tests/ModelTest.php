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
use WebFu\SimpleRepository\Column;
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
             * @column(name="user_id", nullable=false)
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
            #[Column(name: "user_id", nullable: false)]
            protected int $id;
            #[Column(name: "name", nullable: false, length: 100)]
            protected string $name;
            #[Column(name: "email", nullable: false, length: 150)]
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

    public function testJsonSerialize(): void
    {
        $userClass = new class extends Model {
            /**
             * @column(name="user_id", nullable=false)
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

        $this->assertEquals([
            'user_id' => 1,
            'name'    => 'John Doe',
            'email'   => 'john.doe@none.com',
        ], $user->jsonSerialize());
    }

    public function testModelDatetimeWithAnnotations(): void
    {
        $eventClass = new class extends Model {
            /**
             * @column(name="created_at", type="datetime", nullable=false)
             */
            protected \DateTime $createdAt;

            public function getCreatedAt(): \DateTime
            {
                return $this->createdAt;
            }
        };

        $event = new $eventClass([
            'created_at' => '2026-06-04 10:11:12',
        ]);

        $this->assertInstanceOf(\DateTime::class, $event->getCreatedAt());
        $this->assertEquals('2026-06-04T10:11:12+00:00', $event->getCreatedAt()->format(DATE_ATOM));
        $this->assertEquals([
            'created_at' => '2026-06-04T10:11:12+00:00'
        ], $event->jsonSerialize());
    }

    public function testModelDatetimeCustomFormatWithAnnotations(): void
    {
        $eventClass = new class extends Model {
            /**
             * @column(name="created_at", type="datetime", nullable=false, format="Y-m-d")
             */
            protected \DateTime $createdAt;

            public function getCreatedAt(): \DateTime
            {
                return $this->createdAt;
            }
        };

        $event = new $eventClass([
            'created_at' => '2026-06-04T10:11:12+00:00',
        ]);

        $this->assertInstanceOf(\DateTime::class, $event->getCreatedAt());
        $this->assertEquals([
            'created_at' => '2026-06-04'
        ], $event->jsonSerialize());
    }

    public function testModelDatetimeWithAttributes(): void
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped('Attributes are supported since PHP 8.0');
        }

        $eventClass = new class extends Model {
            #[Column(name: 'created_at', type: Column::DATETIME, nullable: false)]
            protected \DateTime $createdAt;

            public function getCreatedAt(): \DateTime
            {
                return $this->createdAt;
            }
        };

        $dateTime = new \DateTime('2026-06-04 10:11:12');
        $event    = new $eventClass([
            'created_at' => $dateTime,
        ]);

        $this->assertSame($dateTime, $event->getCreatedAt());
        $this->assertEquals([
            'created_at' => '2026-06-04T10:11:12+00:00'
        ], $event->jsonSerialize());
    }

    public function testModelDatetimeCustomFormatWithAttributes(): void
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped('Attributes are supported since PHP 8.0');
        }

        $eventClass = new class extends Model {
            #[Column(name: 'created_at', type: Column::DATETIME, nullable: false, format: 'Y-m-d')]
            protected \DateTime $createdAt;

            public function getCreatedAt(): \DateTime
            {
                return $this->createdAt;
            }
        };

        $event = new $eventClass([
            'created_at' => '2026-06-04T10:11:12+00:00',
        ]);

        $this->assertInstanceOf(\DateTime::class, $event->getCreatedAt());
        $this->assertEquals([
            'created_at' => '2026-06-04'
        ], $event->jsonSerialize());
    }

    public function testModelDatetimeImmutableWithAnnotations(): void
    {
        $eventClass = new class extends Model {
            /**
             * @column(name="created_at", type="datetime_immutable", nullable=false)
             */
            protected \DateTimeImmutable $createdAt;

            public function getCreatedAt(): \DateTimeImmutable
            {
                return $this->createdAt;
            }
        };

        $event = new $eventClass([
            'created_at' => '2026-06-04T10:11:12+00:00',
        ]);

        $this->assertInstanceOf(\DateTimeImmutable::class, $event->getCreatedAt());
        $this->assertEquals('2026-06-04T10:11:12+00:00', $event->getCreatedAt()->format(DATE_ATOM));
        $this->assertEquals([
            'created_at' => '2026-06-04T10:11:12+00:00'
        ], $event->jsonSerialize());
    }

    public function testModelDatetimeImmutableCustomFormatWithAnnotations(): void
    {
        $eventClass = new class extends Model {
            /**
             * @column(name="created_at", type="datetime_immutable", nullable=false, format="Y-m-d")
             */
            protected \DateTimeImmutable $createdAt;

            public function getCreatedAt(): \DateTimeImmutable
            {
                return $this->createdAt;
            }
        };

        $event = new $eventClass([
            'created_at' => '2026-06-04T10:11:12+00:00',
        ]);

        $this->assertInstanceOf(\DateTimeImmutable::class, $event->getCreatedAt());
        $this->assertEquals([
            'created_at' => '2026-06-04'
        ], $event->jsonSerialize());
    }

    public function testModelDatetimeImmutableWithAttributes(): void
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped('Attributes are supported since PHP 8.0');
        }

        $eventClass = new class extends Model {
            #[Column(name: 'created_at', type: Column::DATETIME_IMMUTABLE, nullable: false)]
            protected \DateTimeImmutable $createdAt;

            public function getCreatedAt(): \DateTimeImmutable
            {
                return $this->createdAt;
            }
        };

        $dateTime = new \DateTimeImmutable('2026-06-04T10:11:12+00:00');
        $event    = new $eventClass([
            'created_at' => $dateTime,
        ]);

        $this->assertSame($dateTime, $event->getCreatedAt());
        $this->assertEquals([
            'created_at' => '2026-06-04T10:11:12+00:00'
        ], $event->jsonSerialize());
    }

    public function testModelDatetimeImmutableCustomFormatWithAttributes(): void
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped('Attributes are supported since PHP 8.0');
        }

        $eventClass = new class extends Model {
            #[Column(name: 'created_at', type: Column::DATETIME_IMMUTABLE, nullable: false, format: 'Y-m-d')]
            protected \DateTimeImmutable $createdAt;

            public function getCreatedAt(): \DateTimeImmutable
            {
                return $this->createdAt;
            }
        };

        $event = new $eventClass([
            'created_at' => '2026-06-04T10:11:12+00:00',
        ]);

        $this->assertInstanceOf(\DateTimeImmutable::class, $event->getCreatedAt());
        $this->assertEquals([
            'created_at' => '2026-06-04'
        ], $event->jsonSerialize());
    }

    public function testAutoTypeCastingWithAnnotations(): void
    {
        $userClass = new class extends Model {
            /**
             * @column(name="user_id")
             */
            protected int $id;

            /**
             * @column(name="score")
             */
            protected float $score;

            /**
             * @column(name="enabled")
             */
            protected bool $enabled;

            /**
             * @column(name="created_at")
             */
            protected \DateTimeImmutable $createdAt;

            public function getId(): int
            {
                return $this->id;
            }

            public function getScore(): float
            {
                return $this->score;
            }

            public function isEnabled(): bool
            {
                return $this->enabled;
            }

            public function getCreatedAt(): \DateTimeImmutable
            {
                return $this->createdAt;
            }
        };

        $user = new $userClass([
            'user_id'    => '12',
            'score'      => '98.7',
            'enabled'    => 'true',
            'created_at' => '2026-06-05T08:00:00+00:00',
        ]);

        $this->assertSame(12, $user->getId());
        $this->assertEquals(98.7, $user->getScore());
        $this->assertTrue($user->isEnabled());
        $expectedCreatedAt = new \DateTimeImmutable('2026-06-05T08:00:00+00:00');
        $this->assertEquals($expectedCreatedAt, $user->getCreatedAt());
    }

    public function testAutoTypeCastingWithAttributes(): void
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped('Attributes are supported since PHP 8.0');
        }

        $userClass = new class extends Model {
            #[Column(name: 'user_id')]
            protected int $id;

            #[Column(name: 'score')]
            protected float $score;

            #[Column(name: 'enabled')]
            protected bool $enabled;

            #[Column(name: 'created_at')]
            protected \DateTime $createdAt;

            public function getId(): int
            {
                return $this->id;
            }

            public function getScore(): float
            {
                return $this->score;
            }

            public function isEnabled(): bool
            {
                return $this->enabled;
            }

            public function getCreatedAt(): \DateTime
            {
                return $this->createdAt;
            }
        };

        $user = new $userClass([
            'user_id'    => '7',
            'score'      => 10,
            'enabled'    => '0',
            'created_at' => '2026-06-05T09:30:00+00:00',
        ]);

        $this->assertSame(7, $user->getId());
        $this->assertSame(10.0, $user->getScore());
        $this->assertFalse($user->isEnabled());
        $expectedCreatedAt = new \DateTime('2026-06-05T09:30:00+00:00');
        $this->assertEquals($expectedCreatedAt, $user->getCreatedAt());
    }
}
