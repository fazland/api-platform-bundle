<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Form\Extension;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\Extension\Core\DataMapper\PropertyPathMapper;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\RequestHandlerInterface;
use Symfony\Component\Form\Util\ServerParams;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractRequestHandlerTest extends TestCase
{
    /**
     * @var RequestHandlerInterface
     */
    protected $requestHandler;

    /**
     * @var FormFactory
     */
    protected $factory;

    /**
     * @var null|Request
     */
    protected $request;

    /**
     * @var MockObject|ServerParams
     */
    protected $serverParams;

    protected function setUp(): void
    {
        $this->serverParams = $this->getMockBuilder(ServerParams::class)->onlyMethods(['getNormalizedIniPostMaxSize', 'getContentLength'])->getMock();
        $this->requestHandler = $this->getRequestHandler();
        $this->factory = Forms::createFormFactoryBuilder()->getFormFactory();
        $this->request = null;
    }

    public function methodExceptGetProvider(): iterable
    {
        yield ['POST'];
        yield ['PUT'];
        yield ['DELETE'];
        yield ['PATCH'];
    }

    public function methodProvider(): iterable
    {
        yield ['GET'];
        yield from $this->methodExceptGetProvider();
    }

    /**
     * @dataProvider methodProvider
     */
    public function testSubmitIfNameInRequest(string $method): void
    {
        $form = $this->createForm('param1', $method);

        $this->setRequestData($method, [
            'param1' => 'DATA',
        ]);

        $this->requestHandler->handleRequest($form, $this->request);

        self::assertTrue($form->isSubmitted());
        self::assertSame('DATA', $form->getData());
    }

    /**
     * @dataProvider methodProvider
     */
    public function testDoNotSubmitIfWrongRequestMethod(string $method): void
    {
        $form = $this->createForm('param1', $method);

        $otherMethod = 'POST' === $method ? 'PUT' : 'POST';

        $this->setRequestData($otherMethod, [
            'param1' => 'DATA',
        ]);

        $this->requestHandler->handleRequest($form, $this->request);

        self::assertFalse($form->isSubmitted());
    }

    /**
     * @dataProvider methodExceptGetProvider
     */
    public function testDoNoSubmitSimpleFormIfNameNotInRequestAndNotGetRequest(string $method): void
    {
        $form = $this->createForm('param1', $method, false);

        $this->setRequestData($method, [
            'paramx' => [],
        ]);

        $this->requestHandler->handleRequest($form, $this->request);

        self::assertFalse($form->isSubmitted());
    }

    /**
     * @dataProvider methodExceptGetProvider
     */
    public function testDoNotSubmitCompoundFormIfNameNotInRequestAndNotGetRequest(string $method): void
    {
        $form = $this->createForm('param1', $method, true);

        $this->setRequestData($method, [
            'paramx' => [],
        ]);

        $this->requestHandler->handleRequest($form, $this->request);

        self::assertFalse($form->isSubmitted());
    }

    public function testDoNotSubmitIfNameNotInRequestAndGetRequest(): void
    {
        $form = $this->createForm('param1', 'GET');

        $this->setRequestData('GET', [
            'paramx' => [],
        ]);

        $this->requestHandler->handleRequest($form, $this->request);

        self::assertFalse($form->isSubmitted());
    }

    /**
     * @dataProvider methodProvider
     */
    public function testSubmitFormWithEmptyNameIfAtLeastOneFieldInRequest($method): void
    {
        $form = $this->createForm('', $method, true);
        $form->add($this->createForm('param1'));
        $form->add($this->createForm('param2'));

        $this->setRequestData($method, $requestData = [
            'param1' => 'submitted value',
            'paramx' => 'submitted value',
        ]);

        $this->requestHandler->handleRequest($form, $this->request);

        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->get('param1')->isSubmitted());
        self::assertSame('submitted value', $form->get('param1')->getData());

        if ('PATCH' === $method) {
            self::assertFalse($form->get('param2')->isSubmitted());
        } else {
            self::assertTrue($form->get('param2')->isSubmitted());
        }

        self::assertNull($form->get('param2')->getData());
    }

    /**
     * @dataProvider methodProvider
     */
    public function testDoNotSubmitFormWithEmptyNameIfNoFieldInRequest(string $method): void
    {
        $form = $this->createForm('', $method, true);
        $form->add($this->createForm('param1'));
        $form->add($this->createForm('param2'));

        $this->setRequestData($method, [
            'paramx' => 'submitted value',
        ]);

        $this->requestHandler->handleRequest($form, $this->request);

        self::assertFalse($form->isSubmitted());
    }

    /**
     * @dataProvider methodExceptGetProvider
     */
    public function testMergeParamsAndFiles(string $method): void
    {
        $form = $this->createForm('param1', $method, true);
        $form->add($this->createForm('field1'));
        $form->add($this->createBuilder('field2', false, ['allow_file_upload' => true])->getForm());
        $file = $this->getUploadedFile();

        $this->setRequestData($method, [
            'param1' => [
                'field1' => 'DATA',
            ],
        ], [
            'param1' => [
                'field2' => $file,
            ],
        ]);

        $this->requestHandler->handleRequest($form, $this->request);

        self::assertTrue($form->isSubmitted());
        self::assertSame('DATA', $form->get('field1')->getData());
        self::assertSame($file, $form->get('field2')->getData());
    }

    /**
     * @dataProvider methodExceptGetProvider
     */
    public function testParamTakesPrecedenceOverFile(string $method): void
    {
        $form = $this->createForm('param1', $method);
        $file = $this->getUploadedFile();

        $this->setRequestData($method, [
            'param1' => 'DATA',
        ], [
            'param1' => $file,
        ]);

        $this->requestHandler->handleRequest($form, $this->request);

        self::assertTrue($form->isSubmitted());
        self::assertSame('DATA', $form->getData());
    }

    /**
     * @dataProvider methodExceptGetProvider
     */
    public function testSubmitFileIfNoParam(string $method): void
    {
        $form = $this->createBuilder('param1', false, ['allow_file_upload' => true])
                     ->setMethod($method)
                     ->getForm();
        $file = $this->getUploadedFile();

        $this->setRequestData($method, [
            'param1' => null,
        ], [
            'param1' => $file,
        ]);

        $this->requestHandler->handleRequest($form, $this->request);

        self::assertTrue($form->isSubmitted());
        self::assertSame($file, $form->getData());
    }

    /**
     * @dataProvider methodExceptGetProvider
     */
    public function testSubmitMultipleFiles(string $method): void
    {
        $form = $this->createBuilder('param1', false, ['allow_file_upload' => true])
                     ->setMethod($method)
                     ->getForm();
        $file = $this->getUploadedFile();

        $this->setRequestData($method, [
            'param1' => null,
        ], [
            'param2' => $this->getUploadedFile('2'),
            'param1' => $file,
            'param3' => $this->getUploadedFile('3'),
        ]);

        $this->requestHandler->handleRequest($form, $this->request);

        self::assertTrue($form->isSubmitted());
        self::assertSame($file, $form->getData());
    }

    /**
     * @dataProvider methodExceptGetProvider
     */
    public function testSubmitFileWithNamelessForm(string $method): void
    {
        $form = $this->createForm('', $method, true);
        $fileForm = $this->createBuilder('document', false, ['allow_file_upload' => true])->getForm();
        $form->add($fileForm);
        $file = $this->getUploadedFile();
        $this->setRequestData($method, [
            'document' => null,
        ], [
            'document' => $file,
        ]);
        $this->requestHandler->handleRequest($form, $this->request);

        self::assertTrue($form->isSubmitted());
        self::assertSame($file, $fileForm->getData());
    }

    /**
     * @dataProvider getPostMaxSizeFixtures
     */
    public function testAddFormErrorIfPostMaxSizeExceeded($contentLength, string $iniMax, bool $shouldFail, array $errorParams = []): void
    {
        $this->serverParams->expects(self::once())
                           ->method('getContentLength')
                           ->willReturn($contentLength);
        $this->serverParams->expects(self::any())
                           ->method('getNormalizedIniPostMaxSize')
                           ->willReturn($iniMax);

        $options = ['post_max_size_message' => 'Max {{ max }}!'];
        $form = $this->factory->createNamed('name', TextType::class, null, $options);
        $this->setRequestData('POST', [], []);

        $this->requestHandler->handleRequest($form, $this->request);

        if ($shouldFail) {
            $error = new FormError($options['post_max_size_message'], null, $errorParams);
            $error->setOrigin($form);

            self::assertEquals([$error], \iterator_to_array($form->getErrors()));
            self::assertTrue($form->isSubmitted());
        } else {
            self::assertCount(0, $form->getErrors());
            self::assertFalse($form->isSubmitted());
        }
    }

    public function getPostMaxSizeFixtures(): iterable
    {
        return [
            [(1024 ** 3) + 1, '1G', true, ['{{ max }}' => '1G']],
            [1024 ** 3, '1G', false],
            [(1024 ** 2) + 1, '1M', true, ['{{ max }}' => '1M']],
            [1024 ** 2, '1M', false],
            [1024 + 1, '1K', true, ['{{ max }}' => '1K']],
            [1024, '1K', false],
            [null, '1K', false],
            [1024, '', false],
            [1024, '0', false],
        ];
    }

    public function testUploadedFilesAreAccepted(): void
    {
        self::assertTrue($this->requestHandler->isFileUpload($this->getUploadedFile()));
    }

    public function testInvalidFilesAreRejected(): void
    {
        self::assertFalse($this->requestHandler->isFileUpload($this->getInvalidFile()));
    }

    /**
     * @dataProvider uploadFileErrorCodes
     */
    public function testFailedFileUploadIsTurnedIntoFormError(int $errorCode, ?int $expectedErrorCode): void
    {
        self::assertSame($expectedErrorCode, $this->requestHandler->getUploadFileError($this->getFailedUploadedFile($errorCode)));
    }

    public function uploadFileErrorCodes(): iterable
    {
        yield 'no error' => [UPLOAD_ERR_OK, null];
        yield 'upload_max_filesize ini directive' => [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_INI_SIZE];
        yield 'MAX_FILE_SIZE from form' => [UPLOAD_ERR_FORM_SIZE, UPLOAD_ERR_FORM_SIZE];
        yield 'partially uploaded' => [UPLOAD_ERR_PARTIAL, UPLOAD_ERR_PARTIAL];
        yield 'no file upload' => [UPLOAD_ERR_NO_FILE, UPLOAD_ERR_NO_FILE];
        yield 'missing temporary directory' => [UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_NO_TMP_DIR];
        yield 'write failure' => [UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_CANT_WRITE];
        yield 'stopped by extension' => [UPLOAD_ERR_EXTENSION, UPLOAD_ERR_EXTENSION];
    }

    abstract protected function setRequestData($method, $data, $files = []);

    abstract protected function getRequestHandler();

    abstract protected function getUploadedFile($suffix = '');

    abstract protected function getInvalidFile();

    abstract protected function getFailedUploadedFile($errorCode);

    protected function createForm($name, $method = null, $compound = false): Form
    {
        $config = $this->createBuilder($name, $compound);

        if (null !== $method) {
            $config->setMethod($method);
        }

        return new Form($config);
    }

    protected function createBuilder($name, $compound = false, array $options = []): FormBuilder
    {
        $builder = new FormBuilder($name, null, new EventDispatcher(), $this->getMockBuilder(FormFactoryInterface::class)->getMock(), $options);
        $builder->setCompound($compound);

        if ($compound) {
            $builder->setDataMapper(new PropertyPathMapper());
        }

        return $builder;
    }
}
