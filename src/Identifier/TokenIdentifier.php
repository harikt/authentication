<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Authentication\Identifier;

use Cake\Datasource\EntityInterface;
use Cake\ORM\TableRegistry;
use InvalidArgumentException;
use RuntimeException;

/**
 * Token Identifier
 */
class TokenIdentifier extends AbstractIdentifier
{

    /**
     * Default configuration
     *
     * @var array
     */
    protected $_defaultConfig = [
        'tokenField' => 'token',
        'model' => 'Users',
        'finder' => 'all',
        'tokenVerification' => 'Orm'
    ];

    /**
     * Identify
     *
     * @param array $data Authentication credentials
     * @return false|EntityInterface
     */
    public function identify($data)
    {
        if (!isset($data['token'])) {
            return false;
        }

        $tokenVerification = $this->config('tokenVerification');
        if (is_callable($tokenVerification)) {
            return $tokenVerification($data, $this->config());
        }

        $this->_checkTokenVerification($tokenVerification);

        return $this->_dispatchTokenVerification($tokenVerification, $data['token']);
    }

    /**
     * Checks that the token verification option is a string
     *
     * @throws \InvalidArgumentException When the token is not a string
     * @param mixed $tokenVerification Token verification string.
     * @return void
     */
    protected function _checkTokenVerification($tokenVerification)
    {
        if (!is_string($tokenVerification)) {
            throw new InvalidArgumentException('The `tokenVerification` option is not a string or callable');
        }
    }

    /**
     * Calls the internal token verification method based on the tokenVerification string
     *
     * @param string $tokenVerification Token verification method string.
     * @param string $token Token string.
     * @return false|\Cake\Datasource\EntityInterface
     */
    protected function _dispatchTokenVerification($tokenVerification, $token)
    {
        $method = '_' . $tokenVerification;
        if (!method_exists($this, $method)) {
            throw new RuntimeException(sprintf('Token verification method `%s` does not exist', __CLASS__ . '::' . $method . '()'));
        }

        return $this->{$method}($token);
    }

    /**
     * Lookup the token in the ORM
     *
     * @param string $token The token string.
     * @return bool|\Cake\Datasource\EntityInterface
     */
    protected function _orm($token)
    {
        $config = $this->_config;
        $table = TableRegistry::get($config['model']);

        $options = [
            'conditions' => [$table->aliasField($config['tokenField']) => $token]
        ];

        $finder = $config['finder'];
        if (is_array($finder)) {
            $options += current($finder);
            $finder = key($finder);
        }

        if (!isset($options['token'])) {
            $options['token'] = $token;
        }

        $result = $table->find($finder, $options)->first();
        if (empty($result)) {
            return false;
        }

        return $result;
    }
}
