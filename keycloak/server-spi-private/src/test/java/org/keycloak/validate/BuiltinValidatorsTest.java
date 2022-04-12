package org.keycloak.validate;

import static org.keycloak.validate.ValidatorConfig.configFromMap;

import java.net.URI;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.Collections;
import java.util.List;
import java.util.regex.Pattern;

import org.junit.Assert;
import org.junit.Test;
import org.keycloak.validate.validators.DoubleValidator;
import org.keycloak.validate.validators.IntegerValidator;
import org.keycloak.validate.validators.LengthValidator;
import org.keycloak.validate.validators.OptionsValidator;
import org.keycloak.validate.validators.PatternValidator;
import org.keycloak.validate.validators.UriValidator;

import com.google.common.collect.ImmutableMap;

public class BuiltinValidatorsTest {

    private static final ValidatorConfig valConfigIgnoreEmptyValues = ValidatorConfig.builder().config(AbstractSimpleValidator.IGNORE_EMPTY_VALUE, true).build();

    @Test
    public void testLengthValidator() {

        Validator validator = Validators.lengthValidator();

        // null and empty values handling
        Assert.assertFalse(validator.validate(null, "name", configFromMap(ImmutableMap.of(LengthValidator.KEY_MIN, 1))).isValid());
        Assert.assertFalse(validator.validate("", "name", configFromMap(ImmutableMap.of(LengthValidator.KEY_MIN, 1))).isValid());
        Assert.assertFalse(validator.validate(" ", "name", configFromMap(ImmutableMap.of(LengthValidator.KEY_MIN, 1))).isValid());
        Assert.assertTrue(validator.validate(" ", "name", configFromMap(ImmutableMap.of(LengthValidator.KEY_MAX, 10))).isValid());
        
        //KEYCLOAK-19006 reproducer
        Assert.assertFalse(validator.validate("     ", "name", ValidatorConfig.builder().config(LengthValidator.KEY_MAX, 4).config(AbstractSimpleValidator.IGNORE_EMPTY_VALUE, true).config(LengthValidator.KEY_TRIM_DISABLED, true).build()).isValid());
        
        // min validation only
        Assert.assertTrue(validator.validate("t", "name", ValidatorConfig.builder().config(LengthValidator.KEY_MIN, 1).build()).isValid());
        Assert.assertFalse(validator.validate("tester", "name", ValidatorConfig.builder().config(LengthValidator.KEY_MIN, 7).build()).isValid());
        
        //min value validation with "empty value ignoration" configured
        Assert.assertTrue(validator.validate(null, "name", ValidatorConfig.builder().config(LengthValidator.KEY_MIN, 1).config(AbstractSimpleValidator.IGNORE_EMPTY_VALUE, true).build()).isValid());
        Assert.assertTrue(validator.validate("", "name", ValidatorConfig.builder().config(LengthValidator.KEY_MIN, 1).config(AbstractSimpleValidator.IGNORE_EMPTY_VALUE, true).build()).isValid());
        Assert.assertFalse(validator.validate(" ", "name", ValidatorConfig.builder().config(LengthValidator.KEY_MIN, 1).config(AbstractSimpleValidator.IGNORE_EMPTY_VALUE, true).build()).isValid());
        Assert.assertTrue(validator.validate("t", "name", ValidatorConfig.builder().config(LengthValidator.KEY_MIN, 1).config(AbstractSimpleValidator.IGNORE_EMPTY_VALUE, true).build()).isValid());
        Assert.assertFalse(validator.validate("tester", "name", ValidatorConfig.builder().config(LengthValidator.KEY_MIN, 7).config(AbstractSimpleValidator.IGNORE_EMPTY_VALUE, true).build()).isValid());

        // max validation only
        Assert.assertTrue(validator.validate("tester", "name", ValidatorConfig.builder().config(LengthValidator.KEY_MAX, 8).build()).isValid());
        Assert.assertFalse(validator.validate("tester", "name", ValidatorConfig.builder().config(LengthValidator.KEY_MAX, 4).build()).isValid());
        
        //max value validation with "empty value ignoration" configured
        Assert.assertTrue(validator.validate(null, "name", ValidatorConfig.builder().config(LengthValidator.KEY_MAX, 8).config(AbstractSimpleValidator.IGNORE_EMPTY_VALUE, true).build()).isValid());
        Assert.assertTrue(validator.validate("tester", "name", ValidatorConfig.builder().config(LengthValidator.KEY_MAX, 8).config(AbstractSimpleValidator.IGNORE_EMPTY_VALUE, true).build()).isValid());
        Assert.assertFalse(validator.validate("tester", "name", ValidatorConfig.builder().config(LengthValidator.KEY_MAX, 4).config(AbstractSimpleValidator.IGNORE_EMPTY_VALUE, true).build()).isValid());

        // both validations together
        ValidatorConfig config1 = configFromMap(ImmutableMap.of(LengthValidator.KEY_MIN, 3, LengthValidator.KEY_MAX, 4));
        Assert.assertFalse(validator.validate("te", "name", config1).isValid());
        Assert.assertTrue(validator.validate("tes", "name", config1).isValid());
        Assert.assertTrue(validator.validate("test", "name", config1).isValid());
        Assert.assertFalse(validator.validate("testr", "name", config1).isValid());

        // test value trimming performed by default
        Assert.assertFalse("trim not performed", validator.validate("t ", "name", configFromMap(ImmutableMap.of(LengthValidator.KEY_MIN, 2))).isValid());
        Assert.assertFalse("trim not performed", validator.validate(" t", "name", configFromMap(ImmutableMap.of(LengthValidator.KEY_MIN, 2))).isValid());
        Assert.assertTrue("trim not performed", validator.validate("tr ", "name", configFromMap(ImmutableMap.of(LengthValidator.KEY_MAX, 2))).isValid());
        Assert.assertTrue("trim not performed", validator.validate(" tr", "name", configFromMap(ImmutableMap.of(LengthValidator.KEY_MAX, 2))).isValid());
        
        // test value trimming disabled in config
        Assert.assertTrue("trim disabled but performed", validator.validate("tr ", "name", configFromMap(ImmutableMap.of(LengthValidator.KEY_MIN, 3, LengthValidator.KEY_TRIM_DISABLED, true))).isValid());
        Assert.assertFalse("trim disabled but performed", validator.validate("trr ", "name", configFromMap(ImmutableMap.of(LengthValidator.KEY_MAX, 3, LengthValidator.KEY_TRIM_DISABLED, true))).isValid());
        
        //test correct error message selection
        Assert.assertEquals(LengthValidator.MESSAGE_INVALID_LENGTH_TOO_SHORT,validator.validate("", "name", ValidatorConfig.builder().config(LengthValidator.KEY_MIN, 1).build()).getErrors().iterator().next().getMessage());
        Assert.assertEquals(LengthValidator.MESSAGE_INVALID_LENGTH,validator.validate("", "name", ValidatorConfig.builder().config(LengthValidator.KEY_MIN, 1).config(LengthValidator.KEY_MAX, 10).build()).getErrors().iterator().next().getMessage());
        Assert.assertEquals(LengthValidator.MESSAGE_INVALID_LENGTH_TOO_LONG,validator.validate("aaa", "name", ValidatorConfig.builder().config(LengthValidator.KEY_MAX, 1).build()).getErrors().iterator().next().getMessage());
        Assert.assertEquals(LengthValidator.MESSAGE_INVALID_LENGTH,validator.validate("aaa", "name", ValidatorConfig.builder().config(LengthValidator.KEY_MIN, 1).config(LengthValidator.KEY_MAX, 2).build()).getErrors().iterator().next().getMessage());
    }

    @Test
    public void testLengthValidator_ConfigValidation() {

        // invalid min and max config values
        ValidatorConfig config = new ValidatorConfig(ImmutableMap.of(LengthValidator.KEY_MIN, new Object(), LengthValidator.KEY_MAX, "invalid"));

        ValidationResult result = Validators.validatorConfigValidator().validate(config, LengthValidator.ID).toResult();

        Assert.assertFalse(result.isValid());
        ValidationError[] errors = result.getErrors().toArray(new ValidationError[0]);

        ValidationError error0 = errors[0];
        Assert.assertNotNull(error0);
        Assert.assertEquals(LengthValidator.ID, error0.getValidatorId());
        Assert.assertEquals(LengthValidator.KEY_MIN, error0.getInputHint());

        ValidationError error1 = errors[1];
        Assert.assertNotNull(error1);
        Assert.assertEquals(LengthValidator.ID, error1.getValidatorId());
        Assert.assertEquals(LengthValidator.KEY_MAX, error1.getInputHint());

        // empty config
        result = Validators.validatorConfigValidator().validate(null, LengthValidator.ID).toResult();
        Assert.assertEquals(2, result.getErrors().size());
        result = Validators.validatorConfigValidator().validate(ValidatorConfig.EMPTY, LengthValidator.ID).toResult();
        Assert.assertEquals(2, result.getErrors().size());

        // correct config
        Assert.assertTrue(Validators.validatorConfigValidator().validate(new ValidatorConfig(ImmutableMap.of(LengthValidator.KEY_MIN, "10")), LengthValidator.ID).toResult().isValid());
        Assert.assertTrue(Validators.validatorConfigValidator().validate(new ValidatorConfig(ImmutableMap.of(LengthValidator.KEY_MAX, "10")), LengthValidator.ID).toResult().isValid());
        Assert.assertTrue(Validators.validatorConfigValidator().validate(new ValidatorConfig(ImmutableMap.of(LengthValidator.KEY_MIN, "10", LengthValidator.KEY_MAX, "10")), LengthValidator.ID).toResult().isValid());

        // max is smaller than min
        Assert.assertFalse(Validators.validatorConfigValidator().validate(new ValidatorConfig(ImmutableMap.of(LengthValidator.KEY_MIN, "10", LengthValidator.KEY_MAX, "9")), LengthValidator.ID).toResult().isValid());
    }

    @Test
    public void testEmailValidator() {
        // this also validates StringFormatValidatorBase for simple values

        Validator validator = Validators.emailValidator();

        Assert.assertFalse(validator.validate(null, "email").isValid());
        Assert.assertFalse(validator.validate("", "email").isValid());
        Assert.assertFalse(validator.validate(" ", "email").isValid());
        
        // empty value ignoration configured
        Assert.assertTrue(validator.validate(null, "emptyString", valConfigIgnoreEmptyValues).isValid());
        Assert.assertTrue(validator.validate("", "emptyString", valConfigIgnoreEmptyValues).isValid());
        Assert.assertFalse(validator.validate(" ", "blankString", valConfigIgnoreEmptyValues).isValid());
        
        Assert.assertTrue(validator.validate("admin@example.org", "email").isValid());
        Assert.assertTrue(validator.validate("admin+sds@example.org", "email").isValid());

        Assert.assertFalse(validator.validate(" ", "email").isValid());
        Assert.assertFalse(validator.validate("adminATexample.org", "email").isValid());
    }

    @Test
    public void testAbstractSimpleValidatorSupportForCollections() {

        Validator validator = Validators.emailValidator();

        List<String> valuesCollection = new ArrayList<>();

        Assert.assertTrue(validator.validate(valuesCollection, "email").isValid());

        valuesCollection.add("");
        Assert.assertFalse(validator.validate(valuesCollection, "email").isValid());
        valuesCollection.add("admin@example.org");
        Assert.assertTrue(validator.validate("admin@example.org", "email").isValid());

        // wrong value fails validation even it is not at first position
        valuesCollection.add(" ");
        Assert.assertFalse(validator.validate(valuesCollection, "email").isValid());

        valuesCollection.remove(valuesCollection.size() - 1);
        valuesCollection.add("adminATexample.org");
        Assert.assertFalse(validator.validate(valuesCollection, "email").isValid());

    }

    @Test
    public void testNotBlankValidator() {

        Validator validator = Validators.notBlankValidator();

        // simple String value
        Assert.assertTrue(validator.validate("tester", "username").isValid());
        Assert.assertFalse(validator.validate("", "username").isValid());
        Assert.assertFalse(validator.validate("   ", "username").isValid());
        Assert.assertFalse(validator.validate(null, "username").isValid());

        // collection as input
        Assert.assertTrue(validator.validate(Arrays.asList("a", "b"), "username").isValid());
        Assert.assertFalse(validator.validate(new ArrayList<>(), "username").isValid());
        Assert.assertFalse(validator.validate(Arrays.asList(""), "username").isValid());
        Assert.assertFalse(validator.validate(Arrays.asList(" "), "username").isValid());
        Assert.assertFalse(validator.validate(Arrays.asList("a", " "), "username").isValid());
        Assert.assertFalse(validator.validate(Arrays.asList("a", new Object()), "username").isValid());

        // unsupported input type
        Assert.assertFalse(validator.validate(new Object(), "username").isValid());
    }

    @Test
    public void testNotEmptyValidator() {

        Validator validator = Validators.notEmptyValidator();

        Assert.assertTrue(validator.validate("tester", "username").isValid());
        Assert.assertTrue(validator.validate(" ", "username").isValid());
        Assert.assertTrue(validator.validate(Arrays.asList(1, 2, 3), "numberList").isValid());
        Assert.assertTrue(validator.validate(Collections.singleton("key"), "stringSet").isValid());
        Assert.assertTrue(validator.validate(Collections.singletonMap("key", "value"), "stringMap").isValid());

        Assert.assertFalse(validator.validate(null, "username").isValid());
        Assert.assertFalse(validator.validate("", "username").isValid());
        Assert.assertFalse(validator.validate(Collections.emptyList(), "emptyList").isValid());
        Assert.assertFalse(validator.validate(Collections.emptySet(), "emptySet").isValid());
        Assert.assertFalse(validator.validate(Collections.emptyMap(), "emptyMap").isValid());
    }

    @Test
    public void testDoubleValidator() {

        Validator validator = Validators.doubleValidator();

        // null value and empty String
        Assert.assertFalse(validator.validate(null, "null").isValid());
        Assert.assertFalse(validator.validate("", "emptyString").isValid());
        Assert.assertFalse(validator.validate(" ", "blankString").isValid());
        
        // empty value ignoration configured
        Assert.assertTrue(validator.validate(null, "emptyString", valConfigIgnoreEmptyValues).isValid());
        Assert.assertTrue(validator.validate("", "emptyString", valConfigIgnoreEmptyValues).isValid());
        Assert.assertTrue(validator.validate(" ", "blankString", valConfigIgnoreEmptyValues).isValid());

        // simple values
        Assert.assertTrue(validator.validate(10, "age").isValid());
        Assert.assertTrue(validator.validate("10", "age").isValid());
        Assert.assertTrue(validator.validate("3.14", "pi").isValid());
        Assert.assertTrue(validator.validate("   3.14   ", "piWithBlank").isValid());

        Assert.assertFalse(validator.validate("a", "notAnumber").isValid());
        Assert.assertFalse(validator.validate(true, "true").isValid());

        // collections
        Assert.assertFalse(validator.validate(Arrays.asList(""), "age").isValid());
        Assert.assertTrue(validator.validate(Arrays.asList(""), "age",valConfigIgnoreEmptyValues).isValid());
        Assert.assertTrue(validator.validate(new ArrayList<>(), "age").isValid());
        Assert.assertTrue(validator.validate(Arrays.asList(10), "age").isValid());
        Assert.assertTrue(validator.validate(Arrays.asList(" 10 "), "age").isValid());
        Assert.assertTrue(validator.validate(Arrays.asList("3.14"), "pi").isValid());
        Assert.assertTrue(validator.validate(Arrays.asList("3.14", 10), "pi").isValid());

        Assert.assertFalse(validator.validate(Arrays.asList("a"), "notAnumber").isValid());
        Assert.assertFalse(validator.validate(Arrays.asList("3.14", "a"), "notANumberPresent").isValid());
        Assert.assertFalse(validator.validate(Arrays.asList("3.14", new Object()), "notANumberPresent").isValid());
        
        // min only
        Assert.assertTrue(validator.validate("10.1", "name", ValidatorConfig.builder().config(DoubleValidator.KEY_MIN, 1.4).build()).isValid());
        Assert.assertFalse(validator.validate("10.1", "name", ValidatorConfig.builder().config(DoubleValidator.KEY_MIN, 100.5).build()).isValid());
        // min behavior around empty values
        Assert.assertFalse(validator.validate(null, "name", ValidatorConfig.builder().config(DoubleValidator.KEY_MIN, 1.1).build()).isValid());
        Assert.assertFalse(validator.validate("", "name", ValidatorConfig.builder().config(DoubleValidator.KEY_MIN, 1.1).build()).isValid());
        Assert.assertFalse(validator.validate(" ", "name", ValidatorConfig.builder().config(DoubleValidator.KEY_MIN, 1.1).build()).isValid());
        Assert.assertTrue(validator.validate(null, "name", ValidatorConfig.builder().config(DoubleValidator.KEY_MIN, 1.1).config(valConfigIgnoreEmptyValues).build()).isValid());
        Assert.assertTrue(validator.validate("", "name", ValidatorConfig.builder().config(DoubleValidator.KEY_MIN, 1.1).config(valConfigIgnoreEmptyValues).build()).isValid());
        Assert.assertTrue(validator.validate(" ", "name", ValidatorConfig.builder().config(DoubleValidator.KEY_MIN, 1.1).config(valConfigIgnoreEmptyValues).build()).isValid());
        
        // max only
        Assert.assertFalse(validator.validate("10.5", "name", ValidatorConfig.builder().config(DoubleValidator.KEY_MAX, 1.1).build()).isValid());
        Assert.assertTrue(validator.validate("10.5", "name", ValidatorConfig.builder().config(DoubleValidator.KEY_MAX, 100.1).build()).isValid());

        // min and max
        Assert.assertFalse(validator.validate("10.09", "name", ValidatorConfig.builder().config(DoubleValidator.KEY_MIN, 10.1).config(DoubleValidator.KEY_MAX, 100).build()).isValid());
        Assert.assertTrue(validator.validate("10.1", "name", ValidatorConfig.builder().config(DoubleValidator.KEY_MIN, 10.1).config(DoubleValidator.KEY_MAX, 100).build()).isValid());
        Assert.assertTrue(validator.validate("100.1", "name", ValidatorConfig.builder().config(DoubleValidator.KEY_MIN, 10.1).config(DoubleValidator.KEY_MAX, 100.1).build()).isValid());
        Assert.assertFalse(validator.validate("100.2", "name", ValidatorConfig.builder().config(DoubleValidator.KEY_MIN, 10.1).config(DoubleValidator.KEY_MAX, 100.1).build()).isValid());
        
        //test correct error message selection
        Assert.assertEquals(DoubleValidator.MESSAGE_NUMBER_OUT_OF_RANGE_TOO_SMALL,validator.validate("10", "name", ValidatorConfig.builder().config(DoubleValidator.KEY_MIN, 100).build()).getErrors().iterator().next().getMessage());
        Assert.assertEquals(DoubleValidator.MESSAGE_NUMBER_OUT_OF_RANGE,validator.validate("10", "name", ValidatorConfig.builder().config(DoubleValidator.KEY_MIN, 100).config(DoubleValidator.KEY_MAX, 1000).build()).getErrors().iterator().next().getMessage());
        Assert.assertEquals(DoubleValidator.MESSAGE_NUMBER_OUT_OF_RANGE,validator.validate("10000", "name", ValidatorConfig.builder().config(DoubleValidator.KEY_MIN, 100).config(DoubleValidator.KEY_MAX, 1000).build()).getErrors().iterator().next().getMessage());
        Assert.assertEquals(DoubleValidator.MESSAGE_NUMBER_OUT_OF_RANGE_TOO_BIG,validator.validate("10000", "name", ValidatorConfig.builder().config(DoubleValidator.KEY_MAX, 1000).build()).getErrors().iterator().next().getMessage());

    }

    @Test
    public void testDoubleValidator_ConfigValidation() {

        // invalid min and max config values
        ValidatorConfig config = new ValidatorConfig(ImmutableMap.of(DoubleValidator.KEY_MIN, new Object(), DoubleValidator.KEY_MAX, "invalid"));

        ValidationResult result = Validators.validatorConfigValidator().validate(config, DoubleValidator.ID).toResult();

        Assert.assertFalse(result.isValid());
        ValidationError[] errors = result.getErrors().toArray(new ValidationError[0]);

        ValidationError error0 = errors[0];
        Assert.assertNotNull(error0);
        Assert.assertEquals(DoubleValidator.ID, error0.getValidatorId());
        Assert.assertEquals(DoubleValidator.KEY_MIN, error0.getInputHint());

        ValidationError error1 = errors[1];
        Assert.assertNotNull(error1);
        Assert.assertEquals(DoubleValidator.ID, error1.getValidatorId());
        Assert.assertEquals(DoubleValidator.KEY_MAX, error1.getInputHint());

        // empty config
        result = Validators.validatorConfigValidator().validate(null, DoubleValidator.ID).toResult();
        Assert.assertEquals(0, result.getErrors().size());
        result = Validators.validatorConfigValidator().validate(ValidatorConfig.EMPTY, DoubleValidator.ID).toResult();
        Assert.assertEquals(0, result.getErrors().size());

        // correct config
        Assert.assertTrue(Validators.validatorConfigValidator().validate(new ValidatorConfig(ImmutableMap.of(DoubleValidator.KEY_MIN, "10.1")), DoubleValidator.ID).toResult().isValid());
        Assert.assertTrue(Validators.validatorConfigValidator().validate(new ValidatorConfig(ImmutableMap.of(DoubleValidator.KEY_MAX, "10.1")), DoubleValidator.ID).toResult().isValid());
        Assert.assertTrue(Validators.validatorConfigValidator().validate(new ValidatorConfig(ImmutableMap.of(DoubleValidator.KEY_MIN, "10.1", DoubleValidator.KEY_MAX, "11")), DoubleValidator.ID).toResult().isValid());

        // max is smaller than min
        Assert.assertFalse(Validators.validatorConfigValidator().validate(new ValidatorConfig(ImmutableMap.of(DoubleValidator.KEY_MIN, "10.1", DoubleValidator.KEY_MAX, "10.1")), DoubleValidator.ID).toResult().isValid());
    }

    @Test
    public void testIntegerValidator() {
        Validator validator = Validators.integerValidator();

        // null value and empty String
        Assert.assertFalse(validator.validate(null, "null").isValid());
        Assert.assertFalse(validator.validate("", "emptyString").isValid());

        // empty value ignoration configured
        Assert.assertTrue(validator.validate(null, "emptyString", valConfigIgnoreEmptyValues).isValid());
        Assert.assertTrue(validator.validate("", "emptyString", valConfigIgnoreEmptyValues).isValid());
        Assert.assertTrue(validator.validate(" ", "blankString", valConfigIgnoreEmptyValues).isValid());

        // simple values
        Assert.assertTrue(validator.validate(10, "age").isValid());
        Assert.assertTrue(validator.validate("10", "age").isValid());

        Assert.assertFalse(validator.validate("3.14", "pi").isValid());
        Assert.assertFalse(validator.validate("   3.14   ", "piWithBlank").isValid());
        Assert.assertFalse(validator.validate("a", "notAnumber").isValid());
        Assert.assertFalse(validator.validate(true, "true").isValid());

        // collections
        Assert.assertTrue(validator.validate(new ArrayList<>(), "age").isValid());
        Assert.assertFalse(validator.validate(Arrays.asList(""), "age").isValid());
        Assert.assertTrue(validator.validate(Arrays.asList(""), "age",valConfigIgnoreEmptyValues).isValid());
        Assert.assertTrue(validator.validate(Arrays.asList(10), "age").isValid());
        Assert.assertFalse(validator.validate(Arrays.asList(" 10 "), "age").isValid());

        Assert.assertFalse(validator.validate(Arrays.asList("3.14"), "pi").isValid());
        Assert.assertFalse(validator.validate(Arrays.asList("3.14", 10), "pi").isValid());
        Assert.assertFalse(validator.validate(Arrays.asList("a"), "notAnumber").isValid());
        Assert.assertFalse(validator.validate(Arrays.asList("10", "a"), "notANumberPresent").isValid());
        Assert.assertFalse(validator.validate(Arrays.asList("10", new Object()), "notANumberPresent").isValid());

        // min only
        Assert.assertTrue(validator.validate("10", "name", ValidatorConfig.builder().config(IntegerValidator.KEY_MIN, 1).build()).isValid());
        Assert.assertFalse(validator.validate("10", "name", ValidatorConfig.builder().config(IntegerValidator.KEY_MIN, 100).build()).isValid());
        // min behavior around empty values
        Assert.assertFalse(validator.validate(null, "name", ValidatorConfig.builder().config(IntegerValidator.KEY_MIN, 1).build()).isValid());
        Assert.assertFalse(validator.validate("", "name", ValidatorConfig.builder().config(IntegerValidator.KEY_MIN, 1).build()).isValid());
        Assert.assertFalse(validator.validate(" ", "name", ValidatorConfig.builder().config(IntegerValidator.KEY_MIN, 1).build()).isValid());
        Assert.assertTrue(validator.validate(null, "name", ValidatorConfig.builder().config(IntegerValidator.KEY_MIN, 1).config(valConfigIgnoreEmptyValues).build()).isValid());
        Assert.assertTrue(validator.validate("", "name", ValidatorConfig.builder().config(IntegerValidator.KEY_MIN, 1).config(valConfigIgnoreEmptyValues).build()).isValid());
        Assert.assertTrue(validator.validate(" ", "name", ValidatorConfig.builder().config(IntegerValidator.KEY_MIN, 1).config(valConfigIgnoreEmptyValues).build()).isValid());
        
        // max only
        Assert.assertFalse(validator.validate("10", "name", ValidatorConfig.builder().config(IntegerValidator.KEY_MAX, 1).build()).isValid());
        Assert.assertTrue(validator.validate("10", "name", ValidatorConfig.builder().config(IntegerValidator.KEY_MAX, 100).build()).isValid());

        // min and max
        Assert.assertFalse(validator.validate("9", "name", ValidatorConfig.builder().config(IntegerValidator.KEY_MIN, 10).config(IntegerValidator.KEY_MAX, 100).build()).isValid());
        Assert.assertTrue(validator.validate("10", "name", ValidatorConfig.builder().config(IntegerValidator.KEY_MIN, 10).config(IntegerValidator.KEY_MAX, 100).build()).isValid());
        Assert.assertTrue(validator.validate("100", "name", ValidatorConfig.builder().config(IntegerValidator.KEY_MIN, 10).config(IntegerValidator.KEY_MAX, 100).build()).isValid());
        Assert.assertFalse(validator.validate("101", "name", ValidatorConfig.builder().config(IntegerValidator.KEY_MIN, 10).config(IntegerValidator.KEY_MAX, 100).build()).isValid());

        Assert.assertTrue(validator.validate(Long.MIN_VALUE, "name").isValid());
        Assert.assertTrue(validator.validate(Long.MAX_VALUE, "name").isValid());
        
        //test correct error message selection
        Assert.assertEquals(IntegerValidator.MESSAGE_NUMBER_OUT_OF_RANGE_TOO_SMALL,validator.validate("10", "name", ValidatorConfig.builder().config(IntegerValidator.KEY_MIN, 100).build()).getErrors().iterator().next().getMessage());
        Assert.assertEquals(IntegerValidator.MESSAGE_NUMBER_OUT_OF_RANGE,validator.validate("10", "name", ValidatorConfig.builder().config(IntegerValidator.KEY_MIN, 100).config(IntegerValidator.KEY_MAX, 1000).build()).getErrors().iterator().next().getMessage());
        Assert.assertEquals(IntegerValidator.MESSAGE_NUMBER_OUT_OF_RANGE,validator.validate("10000", "name", ValidatorConfig.builder().config(IntegerValidator.KEY_MIN, 100).config(IntegerValidator.KEY_MAX, 1000).build()).getErrors().iterator().next().getMessage());
        Assert.assertEquals(IntegerValidator.MESSAGE_NUMBER_OUT_OF_RANGE_TOO_BIG,validator.validate("10000", "name", ValidatorConfig.builder().config(IntegerValidator.KEY_MAX, 1000).build()).getErrors().iterator().next().getMessage());
    }

    @Test
    public void testIntegerValidator_ConfigValidation() {

        // invalid min and max config values
        ValidatorConfig config = new ValidatorConfig(ImmutableMap.of(IntegerValidator.KEY_MIN, new Object(), IntegerValidator.KEY_MAX, "invalid"));

        ValidationResult result = Validators.validatorConfigValidator().validate(config, IntegerValidator.ID).toResult();

        Assert.assertFalse(result.isValid());
        ValidationError[] errors = result.getErrors().toArray(new ValidationError[0]);

        ValidationError error0 = errors[0];
        Assert.assertNotNull(error0);
        Assert.assertEquals(IntegerValidator.ID, error0.getValidatorId());
        Assert.assertEquals(IntegerValidator.KEY_MIN, error0.getInputHint());

        ValidationError error1 = errors[1];
        Assert.assertNotNull(error1);
        Assert.assertEquals(IntegerValidator.ID, error1.getValidatorId());
        Assert.assertEquals(IntegerValidator.KEY_MAX, error1.getInputHint());

        // empty config
        result = Validators.validatorConfigValidator().validate(null, IntegerValidator.ID).toResult();
        Assert.assertEquals(0, result.getErrors().size());
        result = Validators.validatorConfigValidator().validate(ValidatorConfig.EMPTY, IntegerValidator.ID).toResult();
        Assert.assertEquals(0, result.getErrors().size());

        // correct config
        Assert.assertTrue(Validators.validatorConfigValidator().validate(new ValidatorConfig(ImmutableMap.of(IntegerValidator.KEY_MIN, "10")), IntegerValidator.ID).toResult().isValid());
        Assert.assertTrue(Validators.validatorConfigValidator().validate(new ValidatorConfig(ImmutableMap.of(IntegerValidator.KEY_MAX, "10")), IntegerValidator.ID).toResult().isValid());
        Assert.assertTrue(Validators.validatorConfigValidator().validate(new ValidatorConfig(ImmutableMap.of(IntegerValidator.KEY_MIN, "10", IntegerValidator.KEY_MAX, "11")), IntegerValidator.ID).toResult().isValid());

        // max is smaller than min
        Assert.assertFalse(Validators.validatorConfigValidator().validate(new ValidatorConfig(ImmutableMap.of(IntegerValidator.KEY_MIN, "10", IntegerValidator.KEY_MAX, "10")), IntegerValidator.ID).toResult().isValid());
    }

    @Test
    public void testPatternValidator() {

        Validator validator = Validators.patternValidator();

        // Pattern object in the configuration
        ValidatorConfig config = configFromMap(Collections.singletonMap(PatternValidator.CFG_PATTERN, Pattern.compile("^start-.*-end$")));
        Assert.assertTrue(validator.validate("start-1234-end", "value", config).isValid());
        Assert.assertFalse(validator.validate("start___end", "value", config).isValid());

        // String in the configuration
        config = configFromMap(Collections.singletonMap(PatternValidator.CFG_PATTERN, "^start-.*-end$"));
        Assert.assertTrue(validator.validate("start-1234-end", "value", config).isValid());
        Assert.assertFalse(validator.validate("start___end", "value", config).isValid());
        
        //custom error message
        config = ValidatorConfig.builder().config(PatternValidator.CFG_PATTERN, "^start-.*-end$").config(PatternValidator.CFG_ERROR_MESSAGE, "customError").build();
        Assert.assertEquals("customError", validator.validate("start___end", "value", config).getErrors().iterator().next().getMessage());

        // null and empty values handling
        Assert.assertFalse(validator.validate(null, "value", config).isValid());
        Assert.assertFalse(validator.validate("", "value", config).isValid());
        Assert.assertFalse(validator.validate(" ", "value", config).isValid());
        
        // empty value ignoration configured
        Assert.assertTrue(validator.validate(null, "value", valConfigIgnoreEmptyValues).isValid());
        Assert.assertTrue(validator.validate("", "value", valConfigIgnoreEmptyValues).isValid());
        Assert.assertFalse(validator.validate(" ", "value", ValidatorConfig.builder().config(PatternValidator.CFG_PATTERN, "^[^\\s]$").config(valConfigIgnoreEmptyValues).build()).isValid());

    }

    @Test
    public void testUriValidator() throws Exception {

        Validator validator = Validators.uriValidator();

        Assert.assertTrue(validator.validate(null, "baseUrl").isValid());
        Assert.assertTrue(validator.validate("", "baseUrl").isValid());
        Assert.assertTrue(validator.validate("http://localhost:3000/", "baseUrl").isValid());
        Assert.assertTrue(validator.validate("https://localhost:3000/", "baseUrl").isValid());
        Assert.assertTrue(validator.validate("https://localhost:3000/#someFragment", "baseUrl").isValid());

        Assert.assertFalse(validator.validate(" ", "baseUrl").isValid());
        Assert.assertFalse(validator.validate("file:///somefile.txt", "baseUrl").isValid());
        Assert.assertFalse(validator.validate("invalidUrl++@23", "invalidUri").isValid());

        ValidatorConfig config = configFromMap(ImmutableMap.of(UriValidator.KEY_ALLOW_FRAGMENT, false));
        Assert.assertFalse(validator.validate("https://localhost:3000/#someFragment", "baseUrl", config).isValid());

        // it is also possible to call dedicated validation methods on a built-in validator
        Assert.assertTrue(Validators.uriValidator().validateUri(new URI("https://customurl"), Collections.singleton("https"), true, true));

        Assert.assertFalse(Validators.uriValidator().validateUri(new URI("http://customurl"), Collections.singleton("https"), true, true));
    }
    
    @Test
    public void testOptionsValidator(){
        Validator validator = Validators.optionsValidator();
        
        // options not configured - always invalid
        Assert.assertFalse(validator.validate(null, "test", ValidatorConfig.builder().config(OptionsValidator.KEY_OPTIONS, null).build()).isValid());
        Assert.assertFalse(validator.validate("", "test", ValidatorConfig.builder().config(OptionsValidator.KEY_OPTIONS, null).build()).isValid());
        Assert.assertFalse(validator.validate(" ", "test", ValidatorConfig.builder().config(OptionsValidator.KEY_OPTIONS, null).build()).isValid());
        Assert.assertFalse(validator.validate("s", "test", ValidatorConfig.builder().config(OptionsValidator.KEY_OPTIONS, null).build()).isValid());
        
        // options not configured but empty and blanks ignored, others invalid
        Assert.assertTrue(validator.validate(null, "test", ValidatorConfig.builder().config(OptionsValidator.KEY_OPTIONS, null).config(valConfigIgnoreEmptyValues).build()).isValid());
        Assert.assertTrue(validator.validate("", "test", ValidatorConfig.builder().config(OptionsValidator.KEY_OPTIONS, null).config(valConfigIgnoreEmptyValues).build()).isValid());
        Assert.assertFalse(validator.validate(" ", "test", ValidatorConfig.builder().config(OptionsValidator.KEY_OPTIONS, null).config(valConfigIgnoreEmptyValues).build()).isValid());
        Assert.assertFalse(validator.validate("s", "test", ValidatorConfig.builder().config(OptionsValidator.KEY_OPTIONS, null).config(valConfigIgnoreEmptyValues).build()).isValid());
        
        List<String> options = Arrays.asList("opt1", "opt2");
        
        // options configured
        Assert.assertFalse(validator.validate(null, "test", ValidatorConfig.builder().config(OptionsValidator.KEY_OPTIONS, options).build()).isValid());
        Assert.assertFalse(validator.validate("", "test", ValidatorConfig.builder().config(OptionsValidator.KEY_OPTIONS, options).build()).isValid());
        Assert.assertFalse(validator.validate(" ", "test", ValidatorConfig.builder().config(OptionsValidator.KEY_OPTIONS, options).build()).isValid());
        Assert.assertFalse("must be case sensitive", validator.validate("Opt1", "test", ValidatorConfig.builder().config(OptionsValidator.KEY_OPTIONS, options).build()).isValid());
        Assert.assertTrue(validator.validate("opt1", "test", ValidatorConfig.builder().config(OptionsValidator.KEY_OPTIONS, options).build()).isValid());
        Assert.assertTrue(validator.validate("opt2", "test", ValidatorConfig.builder().config(OptionsValidator.KEY_OPTIONS, options).build()).isValid());
        Assert.assertFalse("trim not expected", validator.validate("opt2 ", "test", ValidatorConfig.builder().config(OptionsValidator.KEY_OPTIONS, options).build()).isValid());
        Assert.assertFalse("trim not expected", validator.validate(" opt2", "test", ValidatorConfig.builder().config(OptionsValidator.KEY_OPTIONS, options).build()).isValid());
        
        // options configured - empty and blanks ignored
        Assert.assertTrue(validator.validate(null, "test", ValidatorConfig.builder().config(OptionsValidator.KEY_OPTIONS, options).config(valConfigIgnoreEmptyValues).build()).isValid());
        Assert.assertTrue(validator.validate("", "test", ValidatorConfig.builder().config(OptionsValidator.KEY_OPTIONS, options).config(valConfigIgnoreEmptyValues).build()).isValid());
        Assert.assertFalse(validator.validate(" ", "test", ValidatorConfig.builder().config(OptionsValidator.KEY_OPTIONS, options).config(valConfigIgnoreEmptyValues).build()).isValid());
        Assert.assertFalse("must be case sensitive", validator.validate("Opt1", "test", ValidatorConfig.builder().config(OptionsValidator.KEY_OPTIONS, options).config(valConfigIgnoreEmptyValues).build()).isValid());
        Assert.assertTrue(validator.validate("opt1", "test", ValidatorConfig.builder().config(OptionsValidator.KEY_OPTIONS, options).config(valConfigIgnoreEmptyValues).build()).isValid());
        Assert.assertTrue(validator.validate("opt2", "test", ValidatorConfig.builder().config(OptionsValidator.KEY_OPTIONS, options).config(valConfigIgnoreEmptyValues).build()).isValid());
        Assert.assertFalse("trim not expected", validator.validate(" opt2", "test", ValidatorConfig.builder().config(OptionsValidator.KEY_OPTIONS, options).config(valConfigIgnoreEmptyValues).build()).isValid());
        Assert.assertFalse("trim not expected", validator.validate("opt2 ", "test", ValidatorConfig.builder().config(OptionsValidator.KEY_OPTIONS, options).config(valConfigIgnoreEmptyValues).build()).isValid());
    }

    @Test
    public void testOptionsValidator_Config_Validation() {
        
        ValidationResult result = Validators.validatorConfigValidator().validate(ValidatorConfig.builder().build(), OptionsValidator.ID).toResult();
        Assert.assertFalse(result.isValid());

        // invalid type of the config value
        result = Validators.validatorConfigValidator().validate(ValidatorConfig.builder().config(OptionsValidator.KEY_OPTIONS, "a").build(), OptionsValidator.ID).toResult();
        Assert.assertFalse(result.isValid());
        
        result = Validators.validatorConfigValidator().validate(ValidatorConfig.builder().config(OptionsValidator.KEY_OPTIONS, Arrays.asList("opt1")).build(), OptionsValidator.ID).toResult();
        Assert.assertTrue(result.isValid());

    }
}
