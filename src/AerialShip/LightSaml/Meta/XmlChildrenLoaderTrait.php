<?php

namespace AerialShip\LightSaml\Meta;


class XmlChildrenLoaderTrait
{
    protected function iterateChildrenElements(\DOMElement $xml, \Closure $elementCallback) {
        for ($node = $xml->firstChild; $node !== NULL; $node = $node->nextSibling) {
            if ($node instanceof \DOMElement) {
                $elementCallback($node);
            }
        }
    }

    protected function loadXmlChildren(\DOMElement $xml, array $node2ClassMap, \Closure $itemCallback) {
        $result = array();
        $class = $this;
        $this->iterateChildrenElements($xml, function(\DOMElement $node) use (&$result, $node2ClassMap, $itemCallback, $class) {
            $recognized = $class->doMapping($node, $node2ClassMap, $itemCallback);
            if (!$recognized) {
                $result[] = $node;
            }
        });
        return $result;
    }


    /**
     * @param \DOMElement $node
     * @param array $node2ClassMap
     * @param callable $itemCallback
     * @return \DOMElement|null
     */
    public function doMapping(\DOMElement $node, array $node2ClassMap, \Closure $itemCallback) {
        $recognized = false;
        foreach ($node2ClassMap as $meta) {
            if (!$meta) continue;
            $this->getNodeNameAndNamespaceFromMeta($meta, $nodeName, $nodeNS);
            if ($nodeName == $node->localName
                && (!$nodeNS || $nodeNS == $node->namespaceURI)
            ) {
                $obj = $this->getObjectFromMetaClass($meta, $node);
                $itemCallback($obj);
                $recognized = true;
                break;
            }
        } // foreach $node2ClassMap
        return $recognized;
    }


    private function getNodeNameAndNamespaceFromMeta($meta, &$nodeName, &$nodeNS) {
        if (!is_array($meta)) {
            throw new \InvalidArgumentException('Meta must be array');
        }
        if (!isset($meta['node'])) {
            throw new \InvalidArgumentException('Missing node meta');
        }
        $nodeName = null;
        $nodeNS = null;
        if (is_string($meta['node'])) {
            $nodeName = $meta['node'];
        } else if (is_array($meta['node'])) {
            $nodeName = @$meta['node']['name'];
            $nodeNS = @$meta['node']['ns'];
        }
        if (!$nodeName) {
            throw new \InvalidArgumentException('Missing node name meta');
        }
    }

    /**
     * @param $meta
     * @param \DOMElement $node
     * @throws \InvalidArgumentException
     * @return LoadFromXmlInterface
     */
    private function getObjectFromMetaClass($meta, \DOMElement $node) {
        $class = @$meta['class'];
        if (!$class) {
            throw new \InvalidArgumentException('Missing class meta');
        }
        $obj = new $class();
        if ($obj instanceof LoadFromXmlInterface) {
            $obj->loadFromXml($node);
        } else {
            throw new \InvalidArgumentException("Class $class must implement LoadFromXmlInterface");
        }
        return $obj;
    }

}
