<?php
declare(strict_types=1);

namespace Tudock\DisableTwoFactorAuth\Plugin;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\TwoFactorAuth\Model\TfaSession;

/**
 * Class BypassTwoFactorAuth
 * @package Tudock\DisableTwoFactorAuth\Plugin
 */
class BypassTwoFactorAuth
{
    const XML_PATH_CONFIG_ENABLE = 'twofactorauth/general/enable';
    const XML_PATH_CONFIG_FORCE_DISABLE_FOR_USERS = 'twofactorauth/general/force_disable_for_users';

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /**
     * @var AdminSession
     */
    private $session;

    /**
     * BypassTwoFactorAuth constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        AdminSession $session
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->session = $session;
    }

    /**
     * Enables the bypass of 2FA for admin access.
     * This can be useful within development & integration environments.
     *
     * If 2FA is enabled, return the original result.
     * If 2FA is disabled, always return true so all requests bypass 2FA.
     *
     * NOTE: Always keep 2FA enabled within production environments for security purposes.
     *
     * @param TfaSession $subject
     * @param $result
     * @return bool
     */
    public function afterIsGranted(
        TfaSession $subject,
        $result
    ): bool {
        $flagEnabled = $this->scopeConfig->isSetFlag(self::XML_PATH_CONFIG_ENABLE);
        if (!$flagEnabled || $this->adminCanBypass()) {
            return true;
        }
        return $result;
    }

    private function adminCanBypass(): bool
    {
        $user = $this->session->getUser();
        $configValue = $this->scopeConfig->getValue(self::XML_PATH_CONFIG_FORCE_DISABLE_FOR_USERS) ?? '';
        $bypassUsers = explode(
            ',', $configValue
        );
        return $user !== null && in_array($user->getUserName(), $bypassUsers);
    }
}
