<?php

namespace KreaLab\PaymentBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('krealab_payment');

        // @codingStandardsIgnoreStart
        $rootNode
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->children()
                    ->arrayNode('robokassa')
                    ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('handler_parse_parameters')
                                ->defaultValue('krealab_payment.service.parameters')
                            ->end()
                            ->arrayNode('handlers')
                            ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('success')
                                        ->defaultValue('krealab_payment.service.robokassa:success')
                                    ->end()
                                    ->scalarNode('render_success')
                                        ->defaultValue('krealab_payment.service.robokassa:renderSuccess')
                                    ->end()
                                    ->scalarNode('fail')
                                        ->defaultValue('krealab_payment.service.robokassa:fail')
                                    ->end()
                                    ->scalarNode('render_fail')
                                        ->defaultValue('krealab_payment.service.robokassa:renderFail')
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('payments')
                            ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('url')->defaultValue('%robokassa_url%')->end()
                                    ->scalarNode('login')->defaultValue('%robokassa_login%')->end()
                                    ->scalarNode('pass1')->defaultValue('%robokassa_pass1%')->end()
                                    ->scalarNode('pass2')->defaultValue('%robokassa_pass2%')->end()
                                ->end()
                            ->end()
                            ->arrayNode('routes')
                            ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('callback')
                                        ->defaultValue('homepage')
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('templates')
                            ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('layout')
                                        ->defaultValue('layout.html.twig')
                                    ->end()
                                    ->scalarNode('view_success')
                                        ->defaultValue('@KrealabPayment/Robokassa/success.html.twig')
                                    ->end()
                                    ->scalarNode('view_fail')
                                        ->defaultValue('@KrealabPayment/Robokassa/fail.html.twig')
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('psb')
                    ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('handler_parse_parameters')
                                ->defaultValue('krealab_payment.service.parameters')
                            ->end()
                            ->booleanNode('is_ajax_check_paid')
                                ->defaultTrue()
                            ->end()
                            ->arrayNode('handlers')
                            ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('check_status')
                                        ->defaultValue('krealab_payment.service.psb:getStatusAjax')
                                    ->end()
                                    ->scalarNode('success')
                                        ->defaultValue('krealab_payment.service.psb:success')
                                    ->end()
                                    ->scalarNode('fail')
                                        ->defaultValue('krealab_payment.service.psb:fail')
                                    ->end()
                                    ->scalarNode('success_revert')
                                        ->defaultValue('krealab_payment.service.psb:successRevert')
                                    ->end()
                                    ->scalarNode('fail_revert')
                                        ->defaultValue('krealab_payment.service.psb:failRevert')
                                    ->end()
                                    ->scalarNode('render_info_payment')
                                        ->defaultValue('krealab_payment.service.psb:renderInfo')
                                    ->end()
                                    ->scalarNode('render_info_revert')
                                        ->defaultValue('krealab_payment.service.psb:renderInfoRevert')
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('payments')
                            ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('url')->defaultValue('%psb_url%')->end()
                                    ->scalarNode('key')->defaultValue('%psb_key%')->end()
                                    ->scalarNode('terminal_id')->defaultValue('%psb_terminal_id%')->end()
                                    ->scalarNode('merchant_id')->defaultValue('%psb_merchant_id%')->end()
                                    ->scalarNode('merchant_name')->defaultValue('%psb_merchant_name%')->end()
                                    ->scalarNode('merchant_email')->defaultValue('%psb_merchant_email%')->end()
                                ->end()
                            ->end()
                            ->arrayNode('routes')
                            ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('info')
                                        ->defaultValue('payments_psb_info')
                                    ->end()
                                    ->scalarNode('info_revert')
                                        ->defaultValue('payments_psb_info_revert')
                                    ->end()
                                    ->scalarNode('check_status')
                                        ->defaultValue('payments_psb_ajax_status')
                                    ->end()
                                    ->scalarNode('callback')
                                        ->defaultValue('homepage')
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('templates')
                            ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('layout')
                                        ->defaultValue('layout.html.twig')
                                    ->end()
                                    ->scalarNode('view_info_payment')
                                        ->defaultValue('@KrealabPayment/Psb/info_payment.html.twig')
                                    ->end()
                                    ->scalarNode('view_info_revert')
                                        ->defaultValue('@KrealabPayment/Psb/info_revert.html.twig')
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
        // @codingStandardsIgnoreEnd

        return $treeBuilder;
    }
}
