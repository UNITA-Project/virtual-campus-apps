<div class="input-group col-12">
    <div class="input-group-addon">
        <?= $this->generalOption->formElement->options['inputGroup']['prefix']; ?>
    </div>
    <input
        type="text"
        class="form-control"
        name="question[<?= $this->generalOption->name; ?>]" 
        id="<?= $this->generalOption->name; ?>"
        value="<?= $this->generalOption->formElement->value; ?>"
        <?php foreach ($this->generalOption->formElement->options['attributes'] as $attributeName => $attributeValue) echo $attributeName . '="' . $attributeValue . '"'; ?>
    />
    <?php if (isset($this->generalOption->formElement->options['inputGroup']['suffix'])) : ?>
        <div class="input-group-addon">
            <?= $this->generalOption->formElement->options['inputGroup']['suffix']; ?>
        </div>
    <?php endif; ?>
</div>
