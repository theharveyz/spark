<?php
namespace Spark\Auth;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Parser;
use Phalcon\Cache\BackendInterface;
use Spark\Exception\InvalidArgumentsException;

class JSONWebToken
{
    protected static $jwtInstance = null;

    protected $kvStore;

    protected $signKey;

    public function __construct(BackendInterface $cache, $signKey)
    {
        $this->kvStore = $cache;
        $this->signKey = $signKey;
    }

    public function save($uid, $saveItems = [], $expirationSeconds = 86400)
    {
        if (empty($uid)) {
            throw new InvalidArgumentsException('Uid donot be empty!');
        }
        $expirationSeconds = $expirationSeconds ?: 86400;

        $builder = self::getJWT();
        $signer = new Sha256();
        $now = time();
        $expireAt = $now + $expirationSeconds;

        $token = $builder->setIssuedAt($now)
                         ->setExpiration($expireAt)
                         ->set('uid', $uid)
                         ->sign($signer, $this->signKey)
                         ->getToken();

        $cacheKey = $this->getCacheKey((string)$token);
        $this->kvStore->save($cacheKey, json_encode($saveItems), $expirationSeconds);

        return $token;
    }

    protected function getCacheKey($token)
    {   
        return explode('.', $token)[2];
    }

    protected function parse($token)
    {
        return (new Parser())->parse((string) $token);
    }

    public function find($tokenString)
    {
        $token = $this->parse($tokenString);
        $signer = new Sha256();
        if (empty($token)) {
            return null;
        }
        // 验证加密
        if (!$token->verify($signer, $this->signKey)) {
            return null;
        }
        // 是否过期
        if ($token->isExpired()) {
            return null;
        }
        $key = $this->getCacheKey($tokenString);
        $saveItems = ['uid' => $token->getClaim('uid'), 'expireAt' => $token->getClaim('exp')];
        $saveCacheItems = $this->kvStore->get($key);
        return array_merge($saveItems, json_decode($saveCacheItems, true));
    }

    public function clear($token)
    {
        $key = $this->getCacheKey($token);
        if ($key) {
            return $this->kvStore->delete($key);
        }
        return false;
    }

    public static function getJWT()
    {
        if (static::$jwtInstance) {
            return static::$jwtInstance;
        }
        return new Builder();
    }
}
