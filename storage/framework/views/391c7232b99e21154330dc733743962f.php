<?php
    use Filament\Support\Facades\FilamentAsset;
    use Guava\Calendar\Enums\Context;
    use Filament\Support\Facades\FilamentColor;
    use Filament\Support\View\Components\ButtonComponent;
?>

<?php if (isset($component)) { $__componentOriginalb525200bfa976483b4eaa0b7685c6e24 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb525200bfa976483b4eaa0b7685c6e24 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-widgets::components.widget','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-widgets::widget'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

    <?php if (isset($component)) { $__componentOriginalee08b1367eba38734199cf7829b1d1e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalee08b1367eba38734199cf7829b1d1e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.section.index','data' => ['afterHeader' => $this->getCachedHeaderActionsComponent(),'footer' => $this->getCachedFooterActionsComponent()]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::section'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['after-header' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($this->getCachedHeaderActionsComponent()),'footer' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($this->getCachedFooterActionsComponent())]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>


        <style>
            .ec-event.ec-preview,
            .ec-now-indicator {
                z-index: 30;
            }
        </style>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($heading = $this->getHeading()): ?>
             <?php $__env->slot('heading', null, []); ?> 
                <?php echo e($this->getHeading()); ?>

             <?php $__env->endSlot(); ?>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <div
            wire:ignore
            x-load
            x-load-src="<?php echo e(FilamentAsset::getAlpineComponentSrc('calendar', 'guava/calendar')); ?>"
            x-data="calendar({
                view: <?php echo \Illuminate\Support\Js::from($this->getCalendarView())->toHtml() ?>,
                locale: <?php echo \Illuminate\Support\Js::from($this->getLocale())->toHtml() ?>,
                firstDay: <?php echo \Illuminate\Support\Js::from($this->getFirstDay())->toHtml() ?>,
                dayMaxEvents: <?php echo \Illuminate\Support\Js::from($this->getDayMaxEvents())->toHtml() ?>,
                eventContent: <?php echo \Illuminate\Support\Js::from($this->getEventContentJs())->toHtml() ?>,
                eventClickEnabled: <?php echo \Illuminate\Support\Js::from($this->isEventClickEnabled())->toHtml() ?>,
                eventDragEnabled: <?php echo \Illuminate\Support\Js::from($this->isEventDragEnabled())->toHtml() ?>,
                eventResizeEnabled: <?php echo \Illuminate\Support\Js::from($this->isEventResizeEnabled())->toHtml() ?>,
                noEventsClickEnabled: <?php echo \Illuminate\Support\Js::from($this->isNoEventsClickEnabled())->toHtml() ?>,
                dateClickEnabled: <?php echo \Illuminate\Support\Js::from($this->isDateClickEnabled())->toHtml() ?>,
                dateSelectEnabled: <?php echo \Illuminate\Support\Js::from($this->isDateSelectEnabled())->toHtml() ?>,
                datesSetEnabled: <?php echo \Illuminate\Support\Js::from($this->isDatesSetEnabled())->toHtml() ?>,
                viewDidMountEnabled: <?php echo \Illuminate\Support\Js::from($this->isViewDidMountEnabled())->toHtml() ?>,
                eventAllUpdatedEnabled: <?php echo \Illuminate\Support\Js::from($this->isEventAllUpdatedEnabled())->toHtml() ?>,
                hasDateClickContextMenu: <?php echo \Illuminate\Support\Js::from($this->hasContextMenu(Context::DateClick))->toHtml() ?>,
                hasDateSelectContextMenu: <?php echo \Illuminate\Support\Js::from($this->hasContextMenu(Context::DateSelect))->toHtml() ?>,
                hasEventClickContextMenu: <?php echo \Illuminate\Support\Js::from($this->hasContextMenu(Context::EventClick))->toHtml() ?>,
                hasNoEventsClickContextMenu: <?php echo \Illuminate\Support\Js::from($this->hasContextMenu(Context::NoEventsClick))->toHtml() ?>,
                resources: <?php echo \Illuminate\Support\Js::from($this->getResourcesJs())->toHtml() ?>,
                resourceLabelContent: <?php echo \Illuminate\Support\Js::from($this->getResourceLabelContentJs())->toHtml() ?>,
                theme: <?php echo \Illuminate\Support\Js::from($this->getTheme())->toHtml() ?>,
                options: <?php echo \Illuminate\Support\Js::from($this->getOptions())->toHtml() ?>,
                eventAssetUrl: <?php echo \Illuminate\Support\Js::from(FilamentAsset::getAlpineComponentSrc('calendar-event', 'guava/calendar'))->toHtml() ?>,
            })"
            class="<?php echo \Illuminate\Support\Arr::toCssClasses(FilamentColor::getComponentClasses(ButtonComponent::class, 'primary')); ?>"
        >
            <div data-calendar></div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->hasContextMenu()): ?>
                <?php if (isset($component)) { $__componentOriginal5ff0e703a07de8ebda296413721dea82 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5ff0e703a07de8ebda296413721dea82 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'guava-calendar::components.context-menu','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('guava-calendar::context-menu'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5ff0e703a07de8ebda296413721dea82)): ?>
<?php $attributes = $__attributesOriginal5ff0e703a07de8ebda296413721dea82; ?>
<?php unset($__attributesOriginal5ff0e703a07de8ebda296413721dea82); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5ff0e703a07de8ebda296413721dea82)): ?>
<?php $component = $__componentOriginal5ff0e703a07de8ebda296413721dea82; ?>
<?php unset($__componentOriginal5ff0e703a07de8ebda296413721dea82); ?>
<?php endif; ?>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalee08b1367eba38734199cf7829b1d1e9)): ?>
<?php $attributes = $__attributesOriginalee08b1367eba38734199cf7829b1d1e9; ?>
<?php unset($__attributesOriginalee08b1367eba38734199cf7829b1d1e9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalee08b1367eba38734199cf7829b1d1e9)): ?>
<?php $component = $__componentOriginalee08b1367eba38734199cf7829b1d1e9; ?>
<?php unset($__componentOriginalee08b1367eba38734199cf7829b1d1e9); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal028e05680f6c5b1e293abd7fbe5f9758 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal028e05680f6c5b1e293abd7fbe5f9758 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-actions::components.modals','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-actions::modals'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal028e05680f6c5b1e293abd7fbe5f9758)): ?>
<?php $attributes = $__attributesOriginal028e05680f6c5b1e293abd7fbe5f9758; ?>
<?php unset($__attributesOriginal028e05680f6c5b1e293abd7fbe5f9758); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal028e05680f6c5b1e293abd7fbe5f9758)): ?>
<?php $component = $__componentOriginal028e05680f6c5b1e293abd7fbe5f9758; ?>
<?php unset($__componentOriginal028e05680f6c5b1e293abd7fbe5f9758); ?>
<?php endif; ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb525200bfa976483b4eaa0b7685c6e24)): ?>
<?php $attributes = $__attributesOriginalb525200bfa976483b4eaa0b7685c6e24; ?>
<?php unset($__attributesOriginalb525200bfa976483b4eaa0b7685c6e24); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb525200bfa976483b4eaa0b7685c6e24)): ?>
<?php $component = $__componentOriginalb525200bfa976483b4eaa0b7685c6e24; ?>
<?php unset($__componentOriginalb525200bfa976483b4eaa0b7685c6e24); ?>
<?php endif; ?>
<?php /**PATH /Users/konradgruza/Herd/wspolnota/vendor/guava/calendar/resources/views/widgets/calendar-widget.blade.php ENDPATH**/ ?>