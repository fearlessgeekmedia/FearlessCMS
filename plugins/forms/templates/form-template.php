<?php
if (!defined('PROJECT_ROOT')) {
    die('Direct access not permitted');
}
?>
<div class="fearless-form">
    <form method="post" action="">
        <input type="hidden" name="form_id" value="<?php echo esc_attr($form['id']); ?>">
        
        <?php foreach ($form['fields'] as $field): ?>
        <div class="form-field">
            <label for="<?php echo esc_attr($field['id']); ?>">
                <?php echo esc_html($field['label']); ?>
                <?php if ($field['required']): ?>
                <span class="required">*</span>
                <?php endif; ?>
            </label>
            
            <?php switch ($field['type']):
                case 'textarea': ?>
                    <textarea
                        name="<?php echo esc_attr($field['id']); ?>"
                        id="<?php echo esc_attr($field['id']); ?>"
                        <?php echo $field['required'] ? 'required' : ''; ?>
                    ></textarea>
                    <?php break;
                
                case 'select': ?>
                    <select
                        name="<?php echo esc_attr($field['id']); ?>"
                        id="<?php echo esc_attr($field['id']); ?>"
                        <?php echo $field['required'] ? 'required' : ''; ?>
                    >
                        <option value="">Select an option</option>
                        <?php foreach ($field['options'] as $option): ?>
                        <option value="<?php echo esc_attr($option); ?>">
                            <?php echo esc_html($option); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php break;
                
                case 'radio': ?>
                    <div class="radio-group">
                        <?php foreach ($field['options'] as $option): ?>
                        <label class="radio-label">
                            <input
                                type="radio"
                                name="<?php echo esc_attr($field['id']); ?>"
                                value="<?php echo esc_attr($option); ?>"
                                <?php echo $field['required'] ? 'required' : ''; ?>
                            >
                            <?php echo esc_html($option); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <?php break;
                
                case 'checkbox': ?>
                    <label class="checkbox-label">
                        <input
                            type="checkbox"
                            name="<?php echo esc_attr($field['id']); ?>"
                            id="<?php echo esc_attr($field['id']); ?>"
                            <?php echo $field['required'] ? 'required' : ''; ?>
                        >
                        <?php echo esc_html($field['label']); ?>
                    </label>
                    <?php break;
                
                default: ?>
                    <input
                        type="<?php echo esc_attr($field['type']); ?>"
                        name="<?php echo esc_attr($field['id']); ?>"
                        id="<?php echo esc_attr($field['id']); ?>"
                        <?php echo $field['required'] ? 'required' : ''; ?>
                    >
            <?php endswitch; ?>
        </div>
        <?php endforeach; ?>
        
        <div class="form-submit">
            <button type="submit" class="submit-button">Submit</button>
        </div>
    </form>
</div>

<style>
.fearless-form {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
}

.form-field {
    margin-bottom: 20px;
}

.form-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-field .required {
    color: #dc3545;
}

.form-field input[type="text"],
.form-field input[type="email"],
.form-field textarea,
.form-field select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.form-field textarea {
    min-height: 100px;
}

.radio-group,
.checkbox-label {
    display: block;
    margin: 5px 0;
}

.submit-button {
    background-color: #007bff;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.submit-button:hover {
    background-color: #0056b3;
}
</style> 