<?php


namespace WolfSellers\EnableDisableTfa\Model;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Integration\Api\AdminTokenServiceInterface;
use Magento\Integration\Model\CredentialsValidator;
use Magento\Integration\Model\Oauth\TokenFactory as TokenModelFactory;
use Magento\Integration\Model\ResourceModel\Oauth\Token\CollectionFactory as TokenCollectionFactory;
use Magento\TwoFactorAuth\Api\TfaInterface;
use Magento\TwoFactorAuth\Api\UserConfigRequestManagerInterface;
use Magento\User\Model\User as UserModel;
use Magento\Integration\Model\Oauth\Token\RequestThrottler;
use WolfSellers\EnableDisableTfa\Plugin\Backend\Magento\TwoFactorAuth\Observer\ControllerActionPredispatch;
use Magento\User\Model\UserFactory;

class AdminAccessTokenService extends \Magento\TwoFactorAuth\Model\AdminAccessTokenService
{

    /**
     * Token Model
     *
     * @var TokenModelFactory
     */
    private $tokenModelFactory;

    /**
     * User Model
     *
     * @var UserModel
     */
    private $userModel;

    /**
     * @var CredentialsValidator
     */
    private $validatorHelper;

    /**
     * Token Collection Factory
     *
     * @var TokenCollectionFactory
     */
    private $tokenModelCollectionFactory;

    /**
     * @var RequestThrottler
     */
    private $requestThrottler;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Initialize service
     *
     * @param TokenModelFactory $tokenModelFactory
     * @param UserModel $userModel
     * @param TokenCollectionFactory $tokenModelCollectionFactory
     * @param CredentialsValidator $validatorHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param TfaInterface $tfa
     * @param UserConfigRequestManagerInterface $configRequestManager
     * @param UserFactory $userFactory
     * @param AdminTokenServiceInterface $adminTokenService
     */
    public function __construct(
        TokenModelFactory $tokenModelFactory,
        UserModel $userModel,
        TokenCollectionFactory $tokenModelCollectionFactory,
        CredentialsValidator $validatorHelper,
        ScopeConfigInterface $scopeConfig,
        TfaInterface $tfa,
        UserConfigRequestManagerInterface $configRequestManager,
        UserFactory $userFactory,
        AdminTokenServiceInterface $adminTokenService
    ) {
        $this->tokenModelFactory = $tokenModelFactory;
        $this->userModel = $userModel;
        $this->tokenModelCollectionFactory = $tokenModelCollectionFactory;
        $this->validatorHelper = $validatorHelper;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($tfa, $configRequestManager, $userFactory, $adminTokenService);
    }

    /**
     * Prevent the admin token from being created via the token service
     *
     * @param string $username
     * @param string $password
     * @return string
     * @throws AuthenticationException
     * @throws LocalizedException
     * @throws InputException
     */
    public function createAdminAccessToken($username, $password): string
    {
        if($this->scopeConfig->isSetFlag(ControllerActionPredispatch::TWOFACTOR_AUTH_ENABLED)) {
            parent::createAdminAccessToken($username, $password);
        }
        $this->validatorHelper->validate($username, $password);
        $this->getRequestThrottler()->throttle($username, RequestThrottler::USER_TYPE_ADMIN);
        $this->userModel->login($username, $password);
        if (!$this->userModel->getId()) {
            $this->getRequestThrottler()->logAuthenticationFailure($username, RequestThrottler::USER_TYPE_ADMIN);
            /*
             * This message is same as one thrown in \Magento\Backend\Model\Auth to keep the behavior consistent.
             * Constant cannot be created in Auth Model since it uses legacy translation that doesn't support it.
             * Need to make sure that this is refactored once exception handling is updated in Auth Model.
             */
            throw new AuthenticationException(
                __(
                    'The account sign-in was incorrect or your account is disabled temporarily. '
                    . 'Please wait and try again later.'
                )
            );
        }
        $this->getRequestThrottler()->resetAuthenticationFailuresCount($username, RequestThrottler::USER_TYPE_ADMIN);
        return $this->tokenModelFactory->create()->createAdminToken($this->userModel->getId())->getToken();
    }

    /**
     * Revoke token by admin id.
     *
     * The function will delete the token from the oauth_token table.
     *
     * @param int $adminId
     * @return bool
     * @throws LocalizedException
     */
    public function revokeAdminAccessToken($adminId): bool
    {
        $tokenCollection = $this->tokenModelCollectionFactory->create()->addFilterByAdminId($adminId);
        if ($tokenCollection->getSize() == 0) {
            return true;
        }
        try {
            foreach ($tokenCollection as $token) {
                $token->delete();
            }
        } catch (Exception $e) {
            throw new LocalizedException(__("The tokens couldn't be revoked."));
        }
        return true;
    }

    /**
     * Get request throttler instance
     *
     * @return RequestThrottler
     * @deprecated 100.0.4
     */
    private function getRequestThrottler()
    {
        if (!$this->requestThrottler instanceof RequestThrottler) {
            return ObjectManager::getInstance()->get(RequestThrottler::class);
        }
        return $this->requestThrottler;
    }
}
