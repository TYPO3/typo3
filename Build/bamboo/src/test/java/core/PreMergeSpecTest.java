package core;

import com.atlassian.bamboo.specs.api.builders.plan.Plan;
import com.atlassian.bamboo.specs.api.exceptions.PropertiesValidationException;
import com.atlassian.bamboo.specs.api.util.EntityPropertiesBuilders;
import org.junit.Test;

public class PreMergeSpecTest {
    @Test
    public void checkYourPlanOffline() throws PropertiesValidationException {
        Plan plan = new PreMergeSpec().createPlan();

        EntityPropertiesBuilders.build(plan);
    }
}
