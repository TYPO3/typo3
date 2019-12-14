package utilities;

public class JobFixture {
    private final boolean isLimited;

    public JobFixture(boolean isLimited) {
        this.isLimited = isLimited;
    }

    public boolean isLimited(){
        return isLimited;
    }
}
