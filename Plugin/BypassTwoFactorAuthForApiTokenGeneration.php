<?php
declare(strict_types=1);

namespace Tudock\DisableTwoFactorAuth\Plugin;

use Closure;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Integration\Api\AdminTokenServiceInterface;
use Magento\TwoFactorAuth\Model\AdminAccessTokenService;

/**
 * Class BypassWebApiTwoFactorAuth
 * @package Tudock\DisableTwoFactorAuth\Plugin
 */
class BypassTwoFactorAuthForApiTokenGeneration
{
    const XML_PATH_CONFIG_ENABLE_FOR_API_TOKEN_GENERATION = 'twofactorauth/general/enable_for_api_token_generation';
    const XML_PATH_CONFIG_FORCE_DISABLE_FOR_USERS = 'twofactorauth/general/force_disable_for_users';

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var AdminTokenServiceInterface */
    private $adminTokenService;

    /**
     * BypassTwoFactorAuthForApiTokenGeneration constructor.
     * @param AdminTokenServiceInterface $adminTokenService
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        AdminTokenServiceInterface $adminTokenService,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->adminTokenService = $adminTokenService;
    }

    /**
     * Enables the bypass of 2FA for API token generation.
     * This can be useful for third-party vendors during module development.
     *
     * NOTE: Always keep 2FA enabled within production environments for security purposes.
     *
     * @param AdminAccessTokenService $subject
     * @param Closure $proceed
     * @param $username
     * @param $password
     * @return string
     * @throws AuthenticationException
     * @throws InputException
     * @throws LocalizedException
     */
    public function aroundCreateAdminAccessToken(
        AdminAccessTokenService $subject,
        Closure $proceed,
        $username,
        $password
    ): string {
        $flagEnabled = $this->scopeConfig->isSetFlag(self::XML_PATH_CONFIG_ENABLE_FOR_API_TOKEN_GENERATION);
        if (!$flagEnabled || $this->adminCanBypass($username)) {
            return $this->adminTokenService->createAdminAccessToken($username, $password);
        }
        return $proceed($username, $password);
    }

    private function adminCanBypass($user): bool
    {
        $configValue = $this->scopeConfig->getValue(self::XML_PATH_CONFIG_FORCE_DISABLE_FOR_USERS) ?? '';
        $bypassUsers = explode(
            ',', $configValue
        );
        return $user !== null && in_array($user, $bypassUsers);
    }
}
