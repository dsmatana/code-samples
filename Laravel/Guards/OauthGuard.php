<?php

namespace App\Guards;

use App\User;
use Firebase\JWT\JWT;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Crypt;
use UnexpectedValueException;

class OauthGuard implements Guard {
	const HS256 = 'HS256';
	const HS384 = 'HS384';
	const HS512 = 'HS512';
	const RS256 = 'RS256';
	const RS384 = 'RS384';
	const RS512 = 'RS512';

	const ENCRYPTIONS = [self::HS256, self::HS384, self::HS512, self::RS256, self::RS384, self::RS512];

	/**
	 * Access token
	 *
	 * @var string
	 */
	protected ?string $accessToken;

	/**
	 * @var stdClass
	 */
	protected $accessTokenData;

	/**
	 * @var User
	 */
	protected $user;

	public function __construct() {
		$this->prepareAccessToken();
	}

	/**
	 * Determine if the current user is authenticated.
	 *
	 * @return bool
	 */
	public function check() {
		if (!$this->accessTokenData || !$this->accessTokenData->payload->sub) {
			return false;
		}

		try {
			$publicKeyPath = storage_path('public-keys/'.$this->accessTokenData->payload->aud.'.key');
		
			if (!file_exists($publicKeyPath)) {
				throw new UnexpectedValueException("Public key for client {$this->accessTokenData->payload->aud} does not exist");
			}

			JWT::decode($this->accessToken, file_get_contents($publicKeyPath), self::ENCRYPTIONS);
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Determine if the current user is a guest.
	 *
	 * @return bool
	 */
	public function guest() {
		return ! $this->check();
	}

	/**
	 * Get the currently authenticated user.
	 *
	 * @return \Illuminate\Contracts\Auth\Authenticatable|null
	 */
	public function user() {
		if (!$this->accessTokenData || !$this->accessTokenData->payload->sub) {
			return false;
		}

		if (!$this->user) {
			$this->user = User::whereUuid($this->accessTokenData->payload->sub)->first();

			if (!$this->user) {
				$this->user = User::create(['uuid' => $this->accessTokenData->payload->sub]);
			}
		}
		return $this->user;
	}

	/**
	 * Get the ID for the currently authenticated user.
	 *
	 * @return int|string|null
	 */
	public function id() {
		return $this->user()->id;
	}

	/**
	 * Validate a user's credentials.
	 *
	 * @param  array  $credentials
	 * @return bool
	 */
	public function validate(array $credentials = []) {
		// Not applicable to this project.
	}

	/**
	 * Set the current user.
	 *
	 * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
	 * @return void
	 */
	public function setUser(Authenticatable $user) {
		$this->user = $user;
	}

	/**
	 * Decode JWT token
	 *
	 * @return stdClass
	 */
	protected function prepareAccessToken() {
		$this->accessToken = preg_replace('/^Bearer\s?/', '', request()->header('Authorization')) ?: null;

		if (!$this->accessToken) {
			return;
		}

		$segments = explode('.', $this->accessToken);

		if (count($segments) != 3) {
			throw new UnexpectedValueException('Wrong number of segments');
		}

		list($headb64, $bodyb64, $cryptob64) = $segments;

		if (null === ($header = JWT::jsonDecode(JWT::urlsafeB64Decode($headb64)))) {
			throw new UnexpectedValueException('Invalid header encoding');
		}

		if (null === $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($bodyb64))) {
			throw new UnexpectedValueException('Invalid claims encoding');
		}

		if (false === ($sig = JWT::urlsafeB64Decode($cryptob64))) {
			throw new UnexpectedValueException('Invalid signature encoding');
		}

		$this->accessTokenData = (object) compact('header', 'payload', 'sig');
	}

	/**
	 * Has current user scopes
	 *
	 * @param [string] ...$scopes
	 * @return boolean
	 */
	public function hasScope(...$scopes) {
		if (!$this->accessTokenData) {
			throw new UnexpectedValueException('Access token data not prepared');
		}

		$scopes = collect($scopes)->flatten()->unique();
		$current = collect(explode(' ', $this->accessTokenData->payload->scope));

		return $scopes->diff($current)->count() == 0;
	}
}
