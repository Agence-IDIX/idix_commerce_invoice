<?php

namespace Drupal\idix_commerce_invoice\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\commerce_order\Entity\Order;

class InvoiceAccess {
  public function check(AccountInterface $account, Order $order) {
    if(
      $order->getState()->value == 'completed'
      &&
      $account->isAuthenticated()
      &&
      (
        $account->id() == 1
        ||
        $account->hasPermission('view all invoices')
        ||
        $order->getCustomerId() == $account->id()
      )
    ){
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }
}