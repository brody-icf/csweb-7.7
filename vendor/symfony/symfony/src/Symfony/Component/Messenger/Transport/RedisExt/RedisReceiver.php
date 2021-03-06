<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Messenger\Transport\RedisExt;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\LogicException;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

/**
 * @author Alexander Schranz <alexander@sulu.io>
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class RedisReceiver implements ReceiverInterface
{
    private $connection;
    private $serializer;

    public function __construct(Connection $connection, SerializerInterface $serializer = null)
    {
        $this->connection = $connection;
        $this->serializer = $serializer ?? new PhpSerializer();
    }

    /**
     * {@inheritdoc}
     */
    public function get(): iterable
    {
        $redisEnvelope = $this->connection->get();

        if (null === $redisEnvelope) {
            return [];
        }

        try {
            $envelope = $this->serializer->decode([
                'body' => $redisEnvelope['body'],
                'headers' => $redisEnvelope['headers'],
            ]);
        } catch (MessageDecodingFailedException $exception) {
            $this->connection->reject($redisEnvelope['id']);

            throw $exception;
        }

        return [$envelope->with(new RedisReceivedStamp($redisEnvelope['id']))];
    }

    /**
     * {@inheritdoc}
     */
    public function ack(Envelope $envelope): void
    {
        $this->connection->ack($this->findRedisReceivedStamp($envelope)->getId());
    }

    /**
     * {@inheritdoc}
     */
    public function reject(Envelope $envelope): void
    {
        $this->connection->reject($this->findRedisReceivedStamp($envelope)->getId());
    }

    private function findRedisReceivedStamp(Envelope $envelope): RedisReceivedStamp
    {
        /** @var RedisReceivedStamp|null $redisReceivedStamp */
        $redisReceivedStamp = $envelope->last(RedisReceivedStamp::class);

        if (null === $redisReceivedStamp) {
            throw new LogicException('No RedisReceivedStamp found on the Envelope.');
        }

        return $redisReceivedStamp;
    }
}
