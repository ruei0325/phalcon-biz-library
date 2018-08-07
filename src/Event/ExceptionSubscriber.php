<?php

namespace Codeages\PhalconBiz\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Codeages\Biz\Framework\Service\Exception\ServiceException;
use Codeages\Biz\Framework\Service\Exception\NotFoundException as ServiceNotFoundException;
use Codeages\Biz\Framework\Service\Exception\InvalidArgumentException as ServiceInvalidArgumentException;
use Codeages\Biz\Framework\Service\Exception\AccessDeniedException as ServiceAccessDeniedException;
use Codeages\PhalconBiz\ErrorCode;
use Codeages\PhalconBiz\NotFoundException;
use Codeages\PhalconBiz\Authentication\AuthenticateException;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public function onException(GetResponseForExceptionEvent $event)
    {
        $e = $event->getException();
        $debug = $event->getApp()->isDebug();
        $writeLog = true;

        // 当抛出 ServiceException 并设置了 code 时，才会将异常具体信息暴露给调用方
        if ($e instanceof ServiceException && $e->getCode() > 0) {
            $error = ['code' => $e->getCode(), 'message' => $e->getMessage()];
            $statusCode = 400;
        } elseif ($e instanceof NotFoundException) {
            $error = ['code' => ErrorCode::NOT_FOUND, 'message' => $e->getMessage() ?: 'Route Not Found.'];
            $statusCode = 404;
            // 非Debug模式下，不写入日志
            if (!$debug) {
                $writeLog = false;
            }
        } elseif ($e instanceof ServiceNotFoundException) {
            $error = ['code' => ErrorCode::NOT_FOUND, 'message' => $e->getMessage() ?: 'Some resource is not found.'];
            $statusCode = 404;
        } elseif ($e instanceof \InvalidArgumentException || $e instanceof ServiceInvalidArgumentException) {
            $error = ['code' => ErrorCode::INVALID_ARGUMENT, 'message' => $e->getMessage()];
            $statusCode = 400;
        } elseif ($e instanceof ServiceAccessDeniedException) {
            $error = ['code' => ErrorCode::ACCESS_DENIED, 'message' => $e->getMessage() ?: 'Access denied.'];
            $statusCode = 403;
        } elseif ($e instanceof AuthenticateException) {
            $error = ['code' => $e->getCode() ?: ErrorCode::INVALID_AUTHENTICATION, 'message' => $e->getMessage() ?: 'Invalid authentication.'];
            $statusCode = 401;
        } else {
            $error = [
                'code' => ErrorCode::SERVICE_UNAVAILABLE,
                'message' => $debug ? $e->getMessage() : 'Service unavailable.',
            ];
            $statusCode = 500;
        }

        $error['trace_id'] = time().'_'.substr(hash('md5', uniqid('', true)), 0, 10);

        $error['detail'] = $this->formatExceptionDetail($e);

        if ($writeLog) {
            $event->getDI()['biz']['logger']->error('exception', $error);
        }

        if (!$debug) {
            unset($error['detail']);
        }

        $response = $event->getDI()->get('response');
        $response->setStatusCode($statusCode);
        $response->setContentType('application/json', 'UTF-8');
        $response->setContent(json_encode([
            'error' => $error,
        ]));

        $event->setResponse($response);
    }

    public static function getSubscribedEvents()
    {
        return [
            WebEvents::EXCEPTION => 'onException',
        ];
    }

    protected function formatExceptionDetail($e)
    {
        $error = [
            'type' => get_class($e),
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(), // 这里本来是$e->getTrace()，但当异常的堆栈中包含特殊字符时，调用 getTrace() 这个函数会触发PHP内核崩溃，页面500啥都不输出。
        ];

        if ($e->getPrevious()) {
            $error = [$error];
            $newError = $this->formatExceptionDetail($e->getPrevious());
            array_unshift($error, $newError);
        }

        return $error;
    }
}
