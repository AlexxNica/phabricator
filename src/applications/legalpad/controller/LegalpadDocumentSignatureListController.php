<?php

final class LegalpadDocumentSignatureListController extends LegalpadController {

  private $documentID;
  private $queryKey;
  private $document;

  public function willProcessRequest(array $data) {
    $this->documentID = $data['id'];
    $this->queryKey = idx($data, 'queryKey');
  }

  public function processRequest() {
    $request = $this->getRequest();
    $user = $request->getUser();

    $document = id(new LegalpadDocumentQuery())
      ->setViewer($user)
      ->withIDs(array($this->documentID))
      ->requireCapabilities(
        array(
          PhabricatorPolicyCapability::CAN_VIEW,
          PhabricatorPolicyCapability::CAN_EDIT,
        ))
      ->executeOne();
    if (!$document) {
      return new Aphront404Response();
    }

    $this->document = $document;

    $engine = id(new LegalpadDocumentSignatureSearchEngine())
      ->setDocument($document);

    $controller = id(new PhabricatorApplicationSearchController($request))
      ->setQueryKey($this->queryKey)
      ->setSearchEngine($engine)
      ->setNavigation($this->buildSideNav());

    return $this->delegateToController($controller);
  }

  public function buildSideNav($for_app = false) {
    $user = $this->getRequest()->getUser();

    $nav = new AphrontSideNavFilterView();
    $nav->setBaseURI(new PhutilURI($this->getApplicationURI()));

    id(new LegalpadDocumentSignatureSearchEngine())
      ->setViewer($user)
      ->setDocument($this->document)
      ->addNavigationItems($nav->getMenu());

    return $nav;
  }

  public function buildApplicationCrumbs() {
    $crumbs = parent::buildApplicationCrumbs();

    $crumbs->addTextCrumb(
      $this->document->getMonogram(),
      '/'.$this->document->getMonogram());

    return $crumbs;
  }

}
