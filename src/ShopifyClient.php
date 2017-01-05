<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license.
 */

namespace ZfrShopify;

use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Command\CommandInterface;
use GuzzleHttp\Command\Guzzle\Description;
use GuzzleHttp\Command\Guzzle\GuzzleClient;
use GuzzleHttp\Command\Guzzle\Serializer;
use GuzzleHttp\Command\ServiceClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use ZfrShopify\Exception\RuntimeException;

/**
 * Shopify client used to interact with the Shopify API
 *
 * It also offers several utility, to allow generate URLs needed for the OAuth dance, as well
 * as validating incoming request and webhooks
 *
 * @author Michaël Gallego
 *
 * ARTICLE RELATED METHODS:
 *
 * @method array getArticles(array $args = []) {@command Shopify GetArticles}
 * @method array getBlogArticles(array $args = []) {@command Shopify GetBlogArticles}
 * @method array getArticle(array $args = []) {@command Shopify GetArticle}
 * @method array getBlogArticle(array $args = []) {@command Shopify GetBlogArticle}
 * @method array getArticlesAuthors(array $args = []) {@command Shopify GetArticlesAuthors}
 * @method array getArticlesTags(array $args = []) {@command Shopify GetArticlesTags}
 * @method array createArticle(array $args = []) {@command Shopify CreateArticle}
 * @method array createBlogArticle(array $args = []) {@command Shopify CreateBlogArticle}
 * @method array updateArticle(array $args = []) {@command Shopify UpdateArticle}
 * @method array updateBlogArticle(array $args = []) {@command Shopify UpdateBlogArticle}
 * @method array deleteArticle(array $args = []) {@command Shopify DeleteArticle}
 * @method array deleteBlogArticle(array $args = []) {@command Shopify DeleteBlogArticle}
 *
 * ASSET RELATED METHODS:
 *
 * @method array getAssets(array $args = []) {@command Shopify GetAssets}
 * @method array getAsset(array $args = []) {@command Shopify GetAsset}
 * @method array createAsset(array $args = []) {@command Shopify CreateAsset}
 * @method array updateAsset(array $args = []) {@command Shopify UpdateAsset}
 * @method array deleteAsset(array $args = []) {@command Shopify DeleteAsset}
 *
 * CUSTOM COLLECTION RELATED METHODS:
 *
 * @method array getCustomCollections(array $args = []) {@command Shopify GetCustomCollections}
 * @method array getCustomCollection(array $args = []) {@command Shopify GetCustomCollection}
 * @method array createCustomCollection(array $args = []) {@command Shopify CreateCustomCollection}
 * @method array updateCustomCollection(array $args = []) {@command Shopify UpdateCustomCollection}
 * @method array deleteCustomCollection(array $args = []) {@command Shopify DeleteCustomCollection}
 *
 * EVENTS RELATED METHODS:
 *
 * @method array getEvents(array $args = []) {@command Shopify GetEvents}
 * @method array getEvent(array $args = []) {@command Shopify GetEvent}
 *
 * FULFILLMENTS RELATED METHODS:
 *
 * @method array getFulfillments(array $args = []) {@command Shopify GetFulfillments}
 * @method array getFulfillment(array $args = []) {@command Shopify GetFulfillment}
 * @method array createFulfillment(array $args = []) {@command Shopify CreateFulfillment}
 * @method array updateFilfillment(array $args = []) {@command Shopify UpdateFulfillment}
 * @method array completeFulfillment(array $args = []) {@command Shopify CompleteFulfillment}
 * @method array cancelFulfillment(array $args = []) {@command Shopify CancelFulfillment}
 *
 * METAFIELDS RELATED METHODS:
 * 
 * @method array getMetafields(array $args = []) {@command Shopify GetMetafields}
 * @method array getMetafield(array $args = []) {@command Shopify GetMetafield}
 * @method array createMetafield(array $args = []) {@command Shopify CreateMetafield}
 * @method array updateMetafield(array $args = []) {@command Shopify UpdateMetafield}
 * @method array deleteMetafield(array $args = []) {@command Shopify DeleteMetafield}
 * 
 * ORDER RELATED METHODS:
 *
 * @method array getOrders(array $args = []) {@command Shopify GetOrders}
 * @method array getOrder(array $args = []) {@command Shopify GetOrder}
 * @method array closeOrder(array $args = []) {@command Shopify CloseOrder}
 * @method array openOrder(array $args = []) {@command Shopify OpenOrder}
 * @method array cancelOrder(array $args = []) {@command Shopify CancelOrder}
 *
 * PAGE RELATED METHODS:
 *
 * @method array getPages(array $args = []) {@command Shopify GetPages}
 * @method array getPage(array $args = []) {@command Shopify GetPage}
 * @method array createPage(array $args = []) {@command Shopify CreatePage}
 * @method array updatePage(array $args = []) {@command Shopify UpdatePage}
 * @method array deletePage(array $args = []) {@command Shopify DeletePage}
 *
 * PRODUCT RELATED METHODS:
 *
 * @method array getProducts(array $args = []) {@command Shopify GetProducts}
 * @method array getProduct(array $args = []) {@command Shopify GetProduct}
 * @method array createProduct(array $args = []) {@command Shopify CreateProduct}
 * @method array updateProduct(array $args = []) {@command Shopify UpdateProduct}
 * @method array deleteProduct(array $args = []) {@command Shopify DeleteProduct}
 *
 * PRODUCT IMAGE RELATED METHODS:
 *
 * @method array getProductImages(array $args = []) {@command Shopify GetProductImages}
 * @method array getProductImage(array $args = []) {@command Shopify GetProductImage}
 * @method array createProductImage(array $args = []) {@command Shopify CreateProductImage}
 * @method array updateProductImage(array $args = []) {@command Shopify UpdateProductImage}
 * @method array deleteProductImage(array $args = []) {@command Shopify DeleteProductImage}
 *
 * RECURRING APPLICATION CHARGE RELATED METHODS:
 *
 * @method array getRecurringApplicationCharges(array $args = []) {@command Shopify GetRecurringApplicationCharges}
 * @method array getRecurringApplicationCharge(array $args = []) {@command Shopify GetRecurringApplicationCharge}
 * @method array createRecurringApplicationCharge(array $args = []) {@command Shopify CreateRecurringApplicationCharge}
 * @method array activateRecurringApplicationCharge(array $args = []) {@command Shopify ActivateRecurringApplicationCharge}
 * @method array deleteRecurringApplicationCharge(array $args = []) {@command Shopify DeleteRecurringApplicationCharge}
 *
 * SHOP RELATED METHODS:
 *
 * @method array getShop(array $args = []) {@command Shopify GetShop}
 * 
 * SMART COLLECTION RELATED METHODS:
 *
 * @method array getSmartCollections(array $args = []) {@command Shopify GetSmartCollections}
 * @method array getSmartCollection(array $args = []) {@command Shopify GetSmartCollection}
 * @method array createSmartCollection(array $args = []) {@command Shopify CreateSmartCollection}
 * @method array updateSmartCollection(array $args = []) {@command Shopify UpdateSmartCollection}
 * @method array deleteSmartCollection(array $args = []) {@command Shopify DeleteSmartCollection}
 *
 * THEME RELATED METHODS:
 *
 * @method array getThemes(array $args = []) {@command Shopify GetThemes}
 * @method array getTheme(array $args = []) {@command Shopify GetTheme}
 * @method array createTheme(array $args = []) {@command Shopify CreateTheme}
 * @method array updateTheme(array $args = []) {@command Shopify UpdateTheme}
 * @method array deleteTheme(array $args = []) {@command Shopify DeleteTheme}
 *
 * VARIANT RELATED METHODS:
 *
 * @method array getProductVariants(array $args = []) {@command Shopify GetProductVariants}
 * @method array getProductVariant(array $args = []) {@command Shopify GetProductVariant}
 * @method array createProductVariant(array $args = []) {@command Shopify CreateProductVariant}
 * @method array updateProductVariant(array $args = []) {@command Shopify UpdateProductVariant}
 * @method array deleteProductVariant(array $args = []) {@command Shopify DeleteProductVariant}
 *
 * SCRIPT TAGS RELATED METHODS:
 *
 * @method array getScriptTags(array $args = []) {@command Shopify GetScriptTags}
 * @method array getScriptTag(array $args = []) {@command Shopify GetScriptTag}
 * @method array createScriptTag(array $args = []) {@command Shopify CreateScriptTag}
 * @method array updateScriptTag(array $args = []) {@command Shopify UpdateScriptTag}
 * @method array deleteScriptTag(array $args = []) {@command Shopify DeleteScriptTag}
 *
 * TRANSACTION RELATED METHODS:
 *
 * @method array getTransactions(array $args = []) {@command Shopify GetTransactions}
 * @method array getTransaction(array $args = []) {@command Shopify GetTransaction}
 * @method array createTransaction(array $args = []) {@command Shopify CreateTransaction}
 *
 * USAGE CHARGE RELATED METHODS:
 *
 * @method array getUsageCharges(array $args = []) {@command Shopify GetUsageCharges}
 * @method array getUsageCharge(array $args = []) {@command Shopify GetUsageCharge}
 * @method array createUsageCharge(array $args = []) {@command Shopify CreateUsageCharge}
 * 
 * WEBHOOK RELATED METHODS:
 *
 * @method array getWebhooks(array $args = []) {@command Shopify GetWebhooks}
 * @method array getWebhook(array $args = []) {@command Shopify GetWebhook}
 * @method array createWebhook(array $args = []) {@command Shopify CreateWebhook}
 * @method array updateWebhook(array $args = []) {@command Shopify UpdateWebhook}
 * @method array deleteWebhook(array $args = []) {@command Shopify DeleteWebhook}
 * 
 * OTHER METHODS:
 * 
 * @method array createDelegateAccessToken(array $args = []) {@command Shopify CreateDelegateAccessToken}
 *
 * ITERATOR METHODS:
 *
 * @method \Traversable getArticlesIterator(array $commandArgs = [], array $iteratorArgs = []) {@command Shopify GetArticles}
 * @method \Traversable getBlogArticlesIterator(array $commandArgs = [], array $iteratorArgs = []) {@command Shopify GetBlogArticles}
 * @method \Traversable getCustomCollectionsIterator(array $commandArgs = [], array $iteratorArgs = []) {@command Shopify GetCustomCollections}
 * @method \Traversable getEventsIterator(array $commandArgs = [], array $iteratorArgs = []) {@command Shopify GetEvents}
 * @method \Traversable getFulfillmentsIterator(array $commandArgs = [], array $iteratorArgs = []) {@command Shopify GetFulfillments}
 * @method \Traversable getMetafieldsIterator(array $commandArgs = [], array $iteratorArgs = []) {@command Shopify GetMetafields}
 * @method \Traversable getOrdersIterator(array $commandArgs = [], array $iteratorArgs = []) {@command Shopify GetOrders}
 * @method \Traversable getPagesIterator(array $commandArgs = [], array $iteratorArgs = []) {@command Shopify GetPages}
 * @method \Traversable getProductsIterator(array $commandArgs = [], array $iteratorArgs = []) {@command Shopify GetProducts}
 * @method \Traversable getProductImagesIterator(array $commandArgs = [], array $iteratorArgs = []) {@command Shopify GetProductImages}
 * @method \Traversable getRecurringApplicationChargesIterator(array $commandArgs = [], array $iteratorArgs = []) {@command Shopify GetRecurringApplicationCharges}
 * @method \Traversable getSmartCollectionsIterator(array $commandArgs = [], array $iteratorArgs = []) {@command Shopify GetSmartCollections}
 * @method \Traversable getProductVariantsIterator(array $commandArgs = [], array $iteratorArgs = []) {@command Shopify GetProductVariants}
 * @method \Traversable getWebhooksIterator(array $commandArgs = [], array $iteratorArgs = []) {@command Shopify GetWebhooks}
 */
class ShopifyClient
{
    /**
     * @var ServiceClientInterface
     */
    private $guzzleClient;

    /**
     * @var array
     */
    private $connectionOptions;

    /**
     * @param array                       $connectionOptions
     * @param ServiceClientInterface|null $guzzleClient
     */
    public function __construct(array $connectionOptions, ServiceClientInterface $guzzleClient = null)
    {
        $this->validateConnectionOptions($connectionOptions);
        $this->connectionOptions = $connectionOptions;

        $this->guzzleClient = $guzzleClient ?? $this->createDefaultClient();
    }

    /**
     * {@inheritdoc}
     */
    public function __call($method, $args = [])
    {
        // Allow magic method calls for iterators (e.g. $client-><CommandName>Iterator($params))
        if (substr($method, -8) === 'Iterator') {
            return $this->iterateResources(substr($method, 0, -8), $args);
        }

        $args = $args[0] ?? [];

        if ($this->connectionOptions['private_app']) {
            $args = array_merge($args, [
                '@http' => [
                    'auth' => [$this->connectionOptions['api_key'], $this->connectionOptions['password']]
                ]
            ]);
        } else {
            $args = array_merge($args, [
               '@http' => [
                   'headers' => [
                       'X-Shopify-Access-Token' => $this->connectionOptions['access_token']
                   ]
               ]
            ]);
        }

        $command = $this->guzzleClient->getCommand(ucfirst($method), $args);
        $result  = $this->guzzleClient->execute($command);

        // In Shopify, all API responses wrap the data by the resource name. For instance, using the "/shop" endpoint will wrap
        // the data by the "shop" key. This is a bit inconvenient to use in userland. As a consequence, we always "unwrap" the result.

        $operation = $this->guzzleClient->getDescription()->getOperation($command->getName());
        $rootKey   = $operation->getData('root_key');

        return (null === $rootKey) ? $result->toArray() : $result->toArray()[$rootKey];
    }

    /**
     * Wrap request data around a top-key (only for POST and PUT requests)
     *
     * @internal
     * @param  CommandInterface $command
     * @return RequestInterface
     */
    public function wrapRequestData(CommandInterface $command): RequestInterface
    {
        $operation = $this->guzzleClient->getDescription()->getOperation($command->getName());
        $method    = strtolower($operation->getHttpMethod());
        $rootKey   = $operation->getData('root_key');

        $serializer = new Serializer($this->guzzleClient->getDescription()); // Create a default serializer to handle all the hard-work
        $request    = $serializer($command);

        if (($method === 'post' || $method === 'put') && $rootKey !== null) {
            $newBody = [$rootKey => json_decode($request->getBody()->getContents(), true)];
            $request = $request->withBody(Psr7\stream_for(json_encode($newBody)));
        }

        return $request;
    }

    /**
     * Decide when we should retry a request
     *
     * @param  int                    $retries
     * @param  RequestInterface       $request
     * @param  ResponseInterface|null $response
     * @param  RequestException|null  $exception
     * @return bool
     */
    public function retryDecider(int $retries, RequestInterface $request, ResponseInterface $response = null, RequestException $exception = null): bool
    {
        // Limit the number of retries to 5
        if ($retries >= 5) {
            return false;
        }

        // Retry connection exceptions
        if ($exception instanceof ConnectException) {
            return true;
        }

        // Otherwise, retry when we're having a 429 exception
        if ($exception && $response->getStatusCode() === 429) {
            return true;
        }

        return false;
    }

    /**
     * Basic retry delay
     *
     * @internal
     * @param  int $retries
     * @return int
     */
    public function retryDelay(int $retries): int
    {
        return 1000 * $retries;
    }

    /**
     * Validate all the connection parameters
     *
     * @param array $connectionOptions
     */
    private function validateConnectionOptions(array $connectionOptions)
    {
        if (!isset($connectionOptions['shop'], $connectionOptions['api_key'], $connectionOptions['private_app'])) {
            throw new RuntimeException('"shop", "private_app" and/or "api_key" must be provided when instantiating the Shopify client');
        }

        if ($connectionOptions['private_app'] && !isset($connectionOptions['password'])) {
            throw new RuntimeException('You must specify the "password" option when instantiating the Shopify client for a private app');
        }

        if (!$connectionOptions['private_app'] && !isset($connectionOptions['access_token'])) {
            throw new RuntimeException('You must specify the "access_token" option when instantiating the Shopify client for a public app');
        }
    }

    /**
     * @return ServiceClientInterface
     */
    private function createDefaultClient(): ServiceClientInterface
    {
        $baseUri = 'https://' . str_replace('.myshopify.com', '', $this->connectionOptions['shop']) . '.myshopify.com';

        $handlerStack = HandlerStack::create(new CurlHandler());
        $handlerStack->push(Middleware::retry([$this, 'retryDecider'], [$this, 'retryDelay']));

        $httpClient  = new Client(['base_uri' => $baseUri, 'handler' => $handlerStack]);
        $description = new Description(require __DIR__ . '/ServiceDescription/Shopify-v1.php');

        return new GuzzleClient($httpClient, $description, [$this, 'wrapRequestData']);
    }

    /**
     * @param  string $commandName
     * @param  array  $args
     * @return Generator
     */
    private function iterateResources(string $commandName, array $args): Generator
    {
        $args = $args[0] ?? [];

        // When using the iterator, we force the maximum number of items per page. Also, if no "since_id" is set, we force it to 0 because by
        // default Shopify sort resources by title
        $args['limit']    = 250;
        $args['since_id'] = $args['since_id'] ?? 0;

        do {
            $results = $this->$commandName($args);

            foreach ($results as $result) {
                yield $result;
            }

            // Advance the since_id
            $args['since_id'] = end($results)['id'];
        } while(count($results) >= 250);
    }
}
