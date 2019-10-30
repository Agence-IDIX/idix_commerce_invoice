<?php

namespace Drupal\idix_commerce_invoice\Controller;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\OrderTotalSummaryInterface;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\profile\ProfileViewBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends ControllerBase {

  /**
   * The order type entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $orderTypeStorage;

  /**
   * The order total summary.
   *
   * @var \Drupal\commerce_order\OrderTotalSummaryInterface
   */
  protected $orderTotalSummary;

  /**
   * The entity view builder for profiles.
   *
   * @var \Drupal\profile\ProfileViewBuilder
   */
  protected $profileViewBuilder;

  public function __construct(
    OrderTotalSummaryInterface $orderTotalSummary,
    ProfileViewBuilder $profileViewBuilder
  ) {
    $this->orderTotalSummary = $orderTotalSummary;
    $this->profileViewBuilder = $profileViewBuilder;
  }

  public static function create(ContainerInterface $container) {
    $profileViewBuilder = $container->get('entity_type.manager')->getViewBuilder('profile');
    return new static(
      $container->get('commerce_order.order_total_summary'),
      $profileViewBuilder
    );
  }

  public function view(Order $order){
    $build = [
      '#theme' => 'commerce_order_invoice',
      '#order_entity' => $order,
      '#totals' => $this->orderTotalSummary->buildTotals($order),
    ];
    if ($billing_profile = $order->getBillingProfile()) {
      $build['#billing_information'] = $this->profileViewBuilder->view($billing_profile, 'commerce_invoice');
    }
    // @todo : ajouter la gestion de l'adresse de livraison (peu documenter pour le moment :/)

    $build['#attached']['library'] = ['idix_commerce_invoice/commerce_invoice'];

    return $build;

  }

    public function html2pdf(Order $order) {
        $build = [
            '#theme' => 'commerce_order_invoice',
            '#order_entity' => $order,
            '#totals' => $this->orderTotalSummary->buildTotals($order),
        ];
        if ($billing_profile = $order->getBillingProfile()) {
            $build['#billing_information'] = $this->profileViewBuilder->view($billing_profile, 'commerce_invoice');
        }

        $build['#attached']['library'] = ['idix_commerce_invoice/commerce_invoice'];

        $html = \Drupal::service('renderer')->renderPlain($build);

        $mpdf = new \Mpdf\Mpdf(['tempDir' => 'sites/default/files/tmp']);
        $stylesheet = '<style>'.file_get_contents(__DIR__.'/../../css/commerce_invoice_pdf.css').'</style>';

        $mpdf->WriteHTML($stylesheet,1);
        $mpdf->WriteHTML($html,2);
        $mpdf->Output($order->getOrderNumber().".pdf", "D");
    }

}