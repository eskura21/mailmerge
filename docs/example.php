<?php

    /**
     * List of Available Placeholders + Data
     *
     * @access public
     * @var array $placeholders
     */
    $placeholders = array(
        'customer' => $customerModel,
        'appName' => 'Document Mail Merge in PHP',
    );

    // All classes implement appropriate interfaces. These are examples:
    class DatabaseDocumentProvider  implements DocumentProviderInterface        {}
    class EncodeHtmlTransfomer      implements DocumentTransformerInterface     {}
    class TwigParser                implements DocumentTemplateParserInterface  {}
    class WkhtmlPdfGenerator        implements DocumentGeneratorInterface       {}
    class PlaceholderCollection     implements PlaceholderCollectionInterface   {}
    class Placeholder               implements PlaceholderInterface             {}
    // etc...

    // Documents can come from several different providers.
    // Providers provide DocumentTemplateInterface objects. They can filter what documents are available depending
    // on what placeholders are available.
    $providers = (new ProviderStack)
        ->push(new DatabaseDocumentProvider)
        ->push(new TwigTemplateDocumentProvider)
        ->push(new ClassMethodDocumentProvider)
        ->push(new EchoProvider);

    // Document templates can be transformed before being sent to the renderer.
    // TODO: Some way to control whether a transformer gets applied before template parsing, or between template parsing
    //       and document generation. Maybe we should use an event system?
    $transformers = (new TransformerStack)
        ->push(new EncodeHtmlTransformer)
        ->push(new MarkdownTransformer)
        ->push(new AddLineNumbersToCodeBlocksTransformer);

    // Renderer chains should provide ways of producing varying formats (PDF, Word, Image, etc). Perhaps it should only
    // accept one parser so that it is consistent amongst all output formats.
    // Use just one renderer, or chain serveral together to render the document in several formats.
    $renderer = new RenderEngine(new TwigParser, new MicrosoftWordGenerator);
    // Perhaps you want to generate two different formats; a PDF to print and snail-mail to them, but also a HTML
    // document to use as an email.
    $renderer = new RenderEngine(
        new ExpressionLanguageParser,
        (new GeneratorStack)
            -> push(new WkhtmlPdfGenerator('path/to/wkhtmltopdf'))
            -> push(new EmailGenerator)
    );


    // Create the document manager.
    $manager = new DocumentManager(
        ProviderStackInterface      $providers,
        RendererEngineInterface     $renderer,
        TransformerStackInterface   $transformers = null,
        DoctrineCacheProvider       $cache = null
    );

    // Defining Placeholders:
    // When an array of placeholders is passed to the manager, they are automatically converted to a collection.
    // If you want to use an object different to the standard PlaceholderCollection, set your own callback to transform
    // arrays to collections.
    $manager->setPlaceholderObjectCallback(function(array $placeholders) {
        return new YourPlaceholderCollection($placeholders);
    });
    // Or you could just create your placeholder collection before you give it to the document manager.
    $placeholders = new YourPlaceholderCollection($placeholders);
    // If you want to render several documents using the same placeholders, persist the placeholders between renders
    // with DocumentManager::setPersistantPlaceholders().
    $manager->setPersistantPlaceholders(array|PlaceholderCollectionInterface $placeholders)


    // Return a list of documents for the user to choose from. Only those that only rely on placeholders available will
    // be returned.
    $available = $placeholders->getPlaceholderList();
    $documents = $manager->getDocumentList(array|PlaceholderCollectionInterface $available);

    // Once the document has been chosen, render the document and return a DocumentInterface.
    $document = $manager->render($documentName, array|PlaceholderCollectionInterface $placeholders);
